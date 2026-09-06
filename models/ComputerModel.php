<?php

namespace Models;

use DomainException;
use System\Core\Model;

class ComputerModel extends Model
{
    private const SINGLE_INSTANCE_PARTS = [
        'processor', 'motherboard', 'gpu', 'keyboard', 'mouse', 'webcam',
        'pen display', 'pen tablet', 'headset', 'power supply',
    ];

    public function computers(): array
    {
        return $this->all(<<<'SQL'
            SELECT p.PCID, p.PCName, a.EmployeeID, e.FirstName, e.LastName,
                   p.Status, a.AssignmentID, a.AssignedDate, a.ReturnedDate
            FROM pcs p
            LEFT JOIN (
                SELECT a1.*
                FROM assignments a1
                LEFT JOIN assignments a2
                    ON a1.PCID = a2.PCID AND a1.AssignedDate < a2.AssignedDate
                WHERE a1.organization_id = ? AND a2.PCID IS NULL
            ) a ON p.PCID = a.PCID AND a.organization_id = p.organization_id
            LEFT JOIN employees e ON a.EmployeeID = e.EmployeeID AND e.organization_id = p.organization_id
            WHERE p.organization_id = ?
            ORDER BY p.PCID ASC
            SQL, [$this->organizationId(), $this->organizationId()]);
    }

    public function stagedAssignments(): array
    {
        return $this->all(<<<'SQL'
            SELECT t.*, e.FirstName, e.LastName, p.PCName
            FROM temp_assignments t
            LEFT JOIN employees e ON t.EmployeeID = e.EmployeeID AND e.organization_id = t.organization_id
            LEFT JOIN pcs p ON t.PCID = p.PCID AND p.organization_id = t.organization_id
            WHERE t.organization_id = ?
            SQL, [$this->organizationId()]);
    }

    public function returnedCustodySummary(): array
    {
        return $this->all(<<<'SQL'
            SELECT r.id AS resignedID, r.PCID, r.PCName, r.PartID, r.EmployeeID,
                   e.FirstName, e.LastName, r.created_at
            FROM returned_custody r
            LEFT JOIN employees e ON e.EmployeeID = r.EmployeeID
            INNER JOIN (
                SELECT EmployeeID, MAX(id) AS max_id
                FROM returned_custody WHERE organization_id = ?
                GROUP BY EmployeeID
            ) latest ON r.EmployeeID = latest.EmployeeID AND r.id = latest.max_id
            WHERE r.organization_id = ?
            ORDER BY r.PCID DESC
            SQL, [$this->organizationId(), $this->organizationId()]);
    }

    public function stageAssignment(string $employeeSearch, string $computerSearch): void
    {
        $this->transaction(function () use ($employeeSearch, $computerSearch): void {
            $employee = $this->first(
                "SELECT EmployeeID FROM employees "
                . "WHERE organization_id = ? AND (FirstName = ? OR LastName = ? OR EmployeeID = ?) AND LOWER(Status) <> 'resigned'",
                [$this->organizationId(), $employeeSearch, $employeeSearch, $employeeSearch]
            );
            if (!$employee) {
                throw new DomainException('No active employee matched that name or ID.');
            }

            $computer = $this->first(
                "SELECT PCID, PCName FROM pcs WHERE organization_id = ? AND (PCName = ? OR PCID = ?) "
                . "AND LOWER(Status) IN ('unassigned', 'returned')",
                [$this->organizationId(), $computerSearch, $computerSearch]
            );
            if (!$computer) {
                $existing = $this->first(
                    'SELECT PCID, PCName, Status FROM pcs WHERE organization_id = ? AND (PCName = ? OR PCID = ?)',
                    [$this->organizationId(), $computerSearch, $computerSearch]
                );
                if ($existing) {
                    $assignee = $this->first(<<<'SQL'
                        SELECT e.FirstName, e.LastName
                        FROM assignments a
                        INNER JOIN employees e ON a.EmployeeID = e.EmployeeID
                        WHERE a.PCID = ? AND a.organization_id = ? AND LOWER(a.Status) = 'assigned'
                        ORDER BY a.AssignedDate DESC LIMIT 1
                        SQL, [$existing['PCID'], $this->organizationId()]);
                    $suffix = $assignee ? " to {$assignee['FirstName']} {$assignee['LastName']}" : '';
                    throw new DomainException("Computer {$existing['PCName']} is already assigned{$suffix}.");
                }
                throw new DomainException('No available computer matched that name or ID.');
            }

            $conflicts = (int) $this->scalar(<<<'SQL'
                SELECT
                    (SELECT COUNT(*) FROM temp_assignments WHERE organization_id = ? AND (EmployeeID = ? OR PCID = ?))
                    +
                    (SELECT COUNT(*) FROM assignments
                     WHERE organization_id = ? AND (EmployeeID = ? OR PCID = ?) AND LOWER(Status) <> 'returned')
                SQL, [$this->organizationId(), $employee['EmployeeID'], $computer['PCID'], $this->organizationId(), $employee['EmployeeID'], $computer['PCID']]);
            if ($conflicts > 0) {
                throw new DomainException('This employee or computer already has an active assignment.');
            }

            $this->execute(
                'INSERT INTO temp_assignments (organization_id, EmployeeID, PCID) VALUES (?, ?, ?)',
                [$this->organizationId(), $employee['EmployeeID'], $computer['PCID']]
            );
        });
    }

    public function storeAssignments(array $employeeIds, array $pcIds, string $timestamp): array
    {
        return $this->transaction(function () use ($employeeIds, $pcIds, $timestamp): array {
            $summaries = [];
            foreach ($employeeIds as $index => $employeeId) {
                $pcId = (int) $pcIds[$index];
                $this->execute(
                    'INSERT INTO assignments (organization_id, EmployeeID, PCID, created_at) VALUES (?, ?, ?, ?)',
                    [$this->organizationId(), $employeeId, $pcId, $timestamp]
                );
                $this->execute("UPDATE pcs SET Status = 'Assigned', updated_at = ? WHERE PCID = ? AND organization_id = ?", [$timestamp, $pcId, $this->organizationId()]);

                foreach ($this->all('SELECT PartID FROM pc_parts WHERE PCID = ? AND organization_id = ?', [$pcId, $this->organizationId()]) as $part) {
                    $this->execute(
                        'INSERT INTO parts_history (organization_id, PartID, EmployeeID, created_at) VALUES (?, ?, ?, ?)',
                        [$this->organizationId(), $part['PartID'], $employeeId, $timestamp]
                    );
                }

                $summary = $this->first(<<<'SQL'
                    SELECT p.PCName, e.EmployeeID, e.FirstName, e.LastName, e.Email
                    FROM employees e CROSS JOIN pcs p
                    WHERE e.EmployeeID = ? AND p.PCID = ? AND e.organization_id = ? AND p.organization_id = e.organization_id
                    SQL, [$employeeId, $pcId, $this->organizationId()]);
                if ($summary) {
                    $summary['Name'] = $summary['FirstName'] . ' ' . $summary['LastName'];
                    $summaries[] = $summary;
                }
            }
            $this->execute('DELETE FROM temp_assignments WHERE organization_id = ?', [$this->organizationId()]);
            return $summaries;
        });
    }

    public function returnComputer(int $pcId, string $pcName, string $employeeId, string $timestamp): void
    {
        $this->transaction(fn() => $this->returnAssets($pcId, $pcName, $employeeId, $timestamp));
    }

    public function stageComputerForUpdate(string $pcName, string $timestamp): array
    {
        return $this->transaction(function () use ($pcName, $timestamp): array {
            $computer = $this->first('SELECT * FROM pcs WHERE PCName = ? AND organization_id = ?', [$pcName, $this->organizationId()]);
            if (!$computer) {
                throw new DomainException('The specified computer was not found.');
            }
            if ((int) $this->scalar('SELECT COUNT(*) FROM temp_pc WHERE organization_id = ?', [$this->organizationId()]) > 0) {
                throw new DomainException('Finish or reset the current computer update first.');
            }
            $this->execute(
                'INSERT INTO temp_pc (organization_id, PCID, PCName, created_at) VALUES (?, ?, ?, ?)',
                [$this->organizationId(), $computer['PCID'], $computer['PCName'], $timestamp]
            );
            return $computer;
        });
    }

    public function resetUpdateStage(): bool
    {
        return $this->transaction(function (): bool {
            $count = (int) $this->scalar('SELECT COUNT(*) FROM temp_pc WHERE organization_id = ?', [$this->organizationId()])
                + (int) $this->scalar('SELECT COUNT(*) FROM temp_update_pc_parts WHERE organization_id = ?', [$this->organizationId()]);
            if ($count === 0) {
                return false;
            }
            $this->execute('DELETE FROM temp_pc WHERE organization_id = ?', [$this->organizationId()]);
            $this->execute('DELETE FROM temp_update_pc_parts WHERE organization_id = ?', [$this->organizationId()]);
            return true;
        });
    }

    public function stageUpdatePart(array $part): void
    {
        $this->transaction(function () use ($part): void {
            $pcId = $this->scalar('SELECT PCID FROM temp_pc WHERE organization_id = ? LIMIT 1', [$this->organizationId()]);
            if ($pcId === false) {
                throw new DomainException('Search for a computer before selecting parts.');
            }

            $installedTypes = array_map('strtolower', array_column($this->all(<<<'SQL'
                SELECT p.PartType
                FROM pc_parts pp
                INNER JOIN parts p ON p.PartID = pp.PartID
                WHERE pp.PCID = ? AND pp.organization_id = ? AND p.organization_id = pp.organization_id
                SQL, [$pcId, $this->organizationId()]), 'PartType'));
            $partType = strtolower($part['PartType']);
            if (in_array($partType, self::SINGLE_INSTANCE_PARTS, true)
                && in_array($partType, $installedTypes, true)) {
                throw new DomainException("A {$part['PartType']} is already installed on this computer.");
            }

            $this->execute(
                'INSERT INTO temp_update_pc_parts '
                . '(organization_id, PartID, PartType, Brand, Model, SerialNumber, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$this->organizationId(), $part['PartID'], $part['PartType'], $part['Brand'], $part['Model'], $part['SerialNumber'], $part['created_at']]
            );
        });
    }

    public function removeStagedUpdatePart(int $partId): int
    {
        return $this->execute('DELETE FROM temp_update_pc_parts WHERE PartID = ? AND organization_id = ?', [$partId, $this->organizationId()]);
    }

    public function removeStagedAssignment(string $employeeId): int
    {
        return $this->execute('DELETE FROM temp_assignments WHERE EmployeeID = ? AND organization_id = ?', [$employeeId, $this->organizationId()]);
    }

    public function installStagedParts(string $pcName, array $partIds, string $timestamp): array
    {
        return $this->transaction(function () use ($pcName, $partIds, $timestamp): array {
            $pcId = $this->scalar('SELECT PCID FROM temp_pc WHERE PCName = ? AND organization_id = ?', [$pcName, $this->organizationId()]);
            if ($pcId === false) {
                throw new DomainException('Computer was not found in the update staging area.');
            }
            $employee = $this->assignedEmployeeForComputer((int) $pcId);
            $details = [];
            foreach ($partIds as $partId) {
                $this->execute(
                    'INSERT INTO pc_parts (organization_id, PCID, PartID, created_at) VALUES (?, ?, ?, ?)',
                    [$this->organizationId(), $pcId, $partId, $timestamp]
                );
                if ($employee) {
                    $this->execute(
                        'INSERT INTO parts_history (organization_id, PartID, EmployeeID, created_at) VALUES (?, ?, ?, ?)',
                        [$this->organizationId(), $partId, $employee['EmployeeID'], $timestamp]
                    );
                }
                $this->execute("UPDATE parts SET Status = 'In Use', updated_at = ? WHERE PartID = ? AND organization_id = ?", [$timestamp, $partId, $this->organizationId()]);
                $details[] = $this->partDetails((int) $partId);
            }
            $this->execute('DELETE FROM temp_pc WHERE organization_id = ?', [$this->organizationId()]);
            $this->execute('DELETE FROM temp_update_pc_parts WHERE organization_id = ?', [$this->organizationId()]);
            return ['employee' => $employee, 'parts' => array_filter($details)];
        });
    }

    public function specifications(string $pcName): ?array
    {
        $computer = $this->first('SELECT * FROM pcs WHERE PCName = ? AND organization_id = ?', [$pcName, $this->organizationId()]);
        if (!$computer) {
            return null;
        }
        $parts = $this->all(<<<'SQL'
            SELECT p.PartID, p.Brand, p.uniqueID, p.PartType, p.Model, p.SerialNumber, pc.PCName
            FROM pc_parts pp
            INNER JOIN parts p ON pp.PartID = p.PartID
            INNER JOIN pcs pc ON pp.PCID = pc.PCID
            WHERE pp.PCID = ? AND pp.organization_id = ? AND p.organization_id = pp.organization_id AND pc.organization_id = pp.organization_id
            SQL, [$computer['PCID'], $this->organizationId()]);
        return ['computer' => $computer, 'parts' => $parts];
    }

    public function uninstallPart(string $pcName, int $partId, string $partStatus, string $historyStatus, string $timestamp): array
    {
        return $this->transaction(function () use ($pcName, $partId, $partStatus, $historyStatus, $timestamp): array {
            $pcId = $this->scalar('SELECT PCID FROM pcs WHERE PCName = ? AND organization_id = ?', [$pcName, $this->organizationId()]);
            if ($pcId === false) {
                throw new DomainException('Computer not found.');
            }
            $employee = $this->assignedEmployeeForComputer((int) $pcId);
            $this->execute('UPDATE parts SET Status = ?, updated_at = ? WHERE PartID = ? AND organization_id = ?', [$partStatus, $timestamp, $partId, $this->organizationId()]);
            $this->execute('DELETE FROM pc_parts WHERE PartID = ? AND organization_id = ?', [$partId, $this->organizationId()]);
            if ($employee) {
                $this->execute(
                    'INSERT INTO parts_history (organization_id, PartID, EmployeeID, Status, created_at) VALUES (?, ?, ?, ?, ?)',
                    [$this->organizationId(), $partId, $employee['EmployeeID'], $historyStatus, $timestamp]
                );
            }
            return ['employee' => $employee, 'part' => $this->partDetails($partId)];
        });
    }

    public function returnedEquipment(string $employeeId): ?array
    {
        $custody = $this->first(
            'SELECT PCID, EmployeeID, PCName FROM returned_custody WHERE EmployeeID = ? AND organization_id = ? ORDER BY id DESC LIMIT 1',
            [$employeeId, $this->organizationId()]
        );
        if (!$custody) {
            return null;
        }
        $parts = $this->all(<<<'SQL'
            SELECT r.PartID, p.Brand, p.uniqueID, p.PartType, p.Model, p.SerialNumber, r.PCName
            FROM returned_custody r
            INNER JOIN parts p ON p.PartID = r.PartID
            WHERE r.EmployeeID = ? AND r.organization_id = ? AND p.organization_id = r.organization_id
            SQL, [$employeeId, $this->organizationId()]);
        $accessories = $this->all(<<<'SQL'
            SELECT rc.AccessoriesID, rc.AccessoriesPRNumber, rc.AccessoriesName, a.Brand
            FROM returned_custody rc
            LEFT JOIN accessories a ON rc.AccessoriesID = a.AccessoriesID
            WHERE rc.EmployeeID = ? AND rc.PCID = ? AND rc.organization_id = ?
              AND rc.AccessoriesName IS NOT NULL AND rc.AccessoriesName <> ''
            SQL, [$employeeId, $custody['PCID'], $this->organizationId()]);
        return ['custody' => $custody, 'parts' => $parts, 'accessories' => $accessories];
    }

    private function assignedEmployeeForComputer(int $pcId): ?array
    {
        return $this->first(<<<'SQL'
            SELECT e.EmployeeID, e.FirstName, e.LastName, e.Email
            FROM assignments a
            INNER JOIN employees e ON a.EmployeeID = e.EmployeeID
            WHERE a.PCID = ? AND a.organization_id = ? AND LOWER(a.Status) = 'assigned'
            ORDER BY a.updated_at DESC LIMIT 1
            SQL, [$pcId, $this->organizationId()]);
    }

    private function partDetails(int $partId): ?array
    {
        return $this->first(
            'SELECT PRNumber, PartID, PartType, Brand, Model, SerialNumber FROM parts WHERE PartID = ? AND organization_id = ?',
            [$partId, $this->organizationId()]
        );
    }

    private function returnAssets(int $pcId, string $pcName, string $employeeId, string $timestamp): void
    {
        $parts = $this->all(<<<'SQL'
            SELECT DISTINCT pp.PartID
            FROM pc_parts pp
            INNER JOIN parts_history ph ON ph.PartID = pp.PartID
            WHERE ph.EmployeeID = ? AND pp.PCID = ? AND ph.organization_id = ? AND pp.organization_id = ph.organization_id
            SQL, [$employeeId, $pcId, $this->organizationId()]);
        $accessories = $this->all(<<<'SQL'
            SELECT DISTINCT a.AccessoriesID, a.PRNumber, a.AccessoriesName
            FROM accessories_assignments aa
            INNER JOIN accessories a ON a.AccessoriesID = aa.AccessoriesID
            WHERE aa.EmployeeID = ? AND aa.organization_id = ? AND LOWER(aa.Status) = 'assigned' AND a.organization_id = aa.organization_id
            SQL, [$employeeId, $this->organizationId()]);

        foreach ($parts as $part) {
            $this->execute(
                'INSERT INTO returned_custody (organization_id, EmployeeID, PCID, PCName, PartID, created_at) VALUES (?, ?, ?, ?, ?, ?)',
                [$this->organizationId(), $employeeId, $pcId, $pcName, $part['PartID'], $timestamp]
            );
        }
        foreach ($accessories as $accessory) {
            $this->execute(
                'INSERT INTO returned_custody '
                . '(organization_id, EmployeeID, PCID, PCName, AccessoriesID, AccessoriesPRNumber, AccessoriesName, created_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                [$this->organizationId(), $employeeId, $pcId, $pcName, $accessory['AccessoriesID'], $accessory['PRNumber'], $accessory['AccessoriesName'], $timestamp]
            );
        }

        $this->execute(
            "UPDATE assignments SET ReturnedDate = ?, Status = 'Returned', updated_at = ? "
            . "WHERE PCID = ? AND EmployeeID = ? AND organization_id = ? AND LOWER(Status) = 'assigned'",
            [$timestamp, $timestamp, $pcId, $employeeId, $this->organizationId()]
        );
        $this->execute("UPDATE pcs SET Status = 'Returned', updated_at = ? WHERE PCID = ? AND organization_id = ?", [$timestamp, $pcId, $this->organizationId()]);
        $this->execute(
            "UPDATE parts_history SET Status = 'Returned', updated_at = ? "
            . "WHERE EmployeeID = ? AND organization_id = ? AND LOWER(Status) = 'assigned'",
            [$timestamp, $employeeId, $this->organizationId()]
        );
        $this->execute(
            "UPDATE accessories_assignments SET Status = 'Returned', updated_at = ? "
            . "WHERE EmployeeID = ? AND organization_id = ? AND LOWER(Status) = 'assigned'",
            [$timestamp, $employeeId, $this->organizationId()]
        );
        foreach ($accessories as $accessory) {
            $this->execute(
                'UPDATE accessories SET Qty = Qty + 1, AssignedCount = GREATEST(AssignedCount - 1, 0), '
                . 'UpdatedAt = ? WHERE AccessoriesID = ? AND organization_id = ?',
                [$timestamp, $accessory['AccessoriesID'], $this->organizationId()]
            );
        }
    }
}
