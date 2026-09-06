<?php

namespace Models;

use System\Core\Model;

class AccessoriesModel extends Model
{
    public function assignmentHistory(): array
    {
        return $this->all(<<<'SQL'
            SELECT ac.AccessoriesID, ac.Brand, ac.PRNumber, ac.AccessoriesName,
                   ac.Qty, ac.DefectiveCount AS Defective,
                   aa.id AS assignmentID, aa.Status AS assignmentStatus,
                   e.FirstName, e.LastName, e.EmployeeID
            FROM accessories ac
            LEFT JOIN accessories_assignments aa ON ac.AccessoriesID = aa.AccessoriesID AND aa.organization_id = ac.organization_id
            LEFT JOIN employees e ON e.EmployeeID = aa.EmployeeID AND e.organization_id = ac.organization_id
            WHERE ac.organization_id = ?
            ORDER BY ac.AccessoriesName ASC, assignmentStatus ASC
            SQL, [$this->organizationId()]);
    }

    public function allAccessories(): array
    {
        return $this->all('SELECT * FROM accessories WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function stagedAccessories(): array
    {
        return $this->all('SELECT * FROM accessories_temp WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function accessoryNames(): array
    {
        return $this->all('SELECT AccessoriesName FROM accessories WHERE organization_id = ? GROUP BY AccessoriesName', [$this->organizationId()]);
    }

    public function returnHistory(): array
    {
        return $this->all(<<<'SQL'
            SELECT ac.AccessoriesID, ac.Brand, ac.PRNumber, ac.AccessoriesName,
                   aa.id AS assignmentID, aa.Status,
                   e.FirstName, e.LastName, e.EmployeeID
            FROM accessories_assignments aa
            LEFT JOIN accessories ac ON aa.AccessoriesID = ac.AccessoriesID AND ac.organization_id = aa.organization_id
            LEFT JOIN employees e ON e.EmployeeID = aa.EmployeeID AND e.organization_id = aa.organization_id
            WHERE aa.organization_id = ? AND LOWER(aa.Status) = 'returned'
            ORDER BY ac.AccessoriesName ASC, aa.Status ASC
            SQL, [$this->organizationId()]);
    }

    public function returnedAccessoryNames(): array
    {
        return $this->all(<<<'SQL'
            SELECT ac.AccessoriesName, aa.Status
            FROM accessories ac
            INNER JOIN accessories_assignments aa ON aa.AccessoriesID = ac.AccessoriesID AND aa.organization_id = ac.organization_id
            WHERE ac.organization_id = ? AND LOWER(aa.Status) = 'returned'
            GROUP BY ac.AccessoriesName, aa.Status
            SQL, [$this->organizationId()]);
    }

    public function returnedAccessories(): array
    {
        return $this->all(<<<'SQL'
            SELECT ac.*, aa.Status
            FROM accessories ac
            INNER JOIN accessories_assignments aa ON aa.AccessoriesID = ac.AccessoriesID AND aa.organization_id = ac.organization_id
            WHERE ac.organization_id = ? AND LOWER(aa.Status) = 'returned'
            SQL, [$this->organizationId()]);
    }

    public function stage(array $accessory): void
    {
        $this->transaction(function () use ($accessory): void {
            $row = $this->first(
                'SELECT AccessoriesID, Qty FROM accessories_temp '
                . 'WHERE organization_id = ? AND AccessoriesName = ? AND Brand <=> ? AND PRNumber <=> ?',
                [$this->organizationId(), $accessory['AccessoriesName'], $accessory['Brand'], $accessory['PRNumber']]
            );

            if ($row) {
                $this->execute(
                    'UPDATE accessories_temp SET Qty = ?, CreatedAt = ? WHERE AccessoriesID = ? AND organization_id = ?',
                    [(int) $row['Qty'] + $accessory['Qty'], $accessory['CreatedAt'], $row['AccessoriesID'], $this->organizationId()]
                );
                return;
            }

            $this->execute(
                'INSERT INTO accessories_temp (organization_id, AccessoriesName, Brand, Qty, PRNumber, CreatedAt) '
                . 'VALUES (?, ?, ?, ?, ?, ?)',
                [$this->organizationId(), $accessory['AccessoriesName'], $accessory['Brand'], $accessory['Qty'], $accessory['PRNumber'], $accessory['CreatedAt']]
            );
        });
    }

    public function storeStaged(string $createdAt): int
    {
        return $this->transaction(function () use ($createdAt): int {
            $rows = $this->stagedAccessories();
            foreach ($rows as $row) {
                $existing = $this->first(
                    'SELECT AccessoriesID, Qty FROM accessories '
                    . 'WHERE organization_id = ? AND AccessoriesName = ? AND Brand <=> ? AND PRNumber <=> ?',
                    [$this->organizationId(), $row['AccessoriesName'], $row['Brand'], $row['PRNumber']]
                );

                if ($existing) {
                    $this->execute(
                        'UPDATE accessories SET Qty = ?, CreatedAt = ? WHERE AccessoriesID = ? AND organization_id = ?',
                        [(int) $existing['Qty'] + (int) $row['Qty'], $createdAt, $existing['AccessoriesID'], $this->organizationId()]
                    );
                } else {
                    $this->execute(
                        'INSERT INTO accessories (organization_id, AccessoriesName, Brand, Qty, PRNumber, CreatedAt) '
                        . 'VALUES (?, ?, ?, ?, ?, ?)',
                        [$this->organizationId(), $row['AccessoriesName'], $row['Brand'], $row['Qty'], $row['PRNumber'], $createdAt]
                    );
                }
            }

            if ($rows !== []) {
                $this->execute('DELETE FROM accessories_temp WHERE organization_id = ?', [$this->organizationId()]);
            }
            return count($rows);
        });
    }

    public function assign(string $employeeId, array $selections, string $timestamp): array
    {
        return $this->transaction(function () use ($employeeId, $selections, $timestamp): array {
            $warnings = [];
            foreach ($selections as $accessoryName => $identifier) {
                if ($identifier === '') {
                    continue;
                }

                $accessory = $this->first(
                    'SELECT AccessoriesID, Qty, PRNumber FROM accessories '
                    . 'WHERE organization_id = ? AND AccessoriesName = ? AND PRNumber = ?',
                    [$this->organizationId(), $accessoryName, $identifier]
                ) ?? $this->first(
                    'SELECT AccessoriesID, Qty, PRNumber FROM accessories WHERE organization_id = ? AND AccessoriesID = ?',
                    [$this->organizationId(), $identifier]
                );

                if (!$accessory || (int) $accessory['Qty'] <= 0) {
                    $warnings[] = "No available stock for '{$accessoryName}'.";
                    continue;
                }

                $alreadyAssigned = (int) $this->scalar(
                    "SELECT COUNT(*) FROM accessories_assignments "
                    . "WHERE organization_id = ? AND EmployeeID = ? AND AccessoriesID = ? AND LOWER(Status) <> 'returned'",
                    [$this->organizationId(), $employeeId, $accessory['AccessoriesID']]
                );
                if ($alreadyAssigned > 0) {
                    $warnings[] = "Employee {$employeeId} already has '{$accessoryName}' assigned.";
                    continue;
                }

                $this->execute(
                    'UPDATE accessories SET Qty = Qty - 1, AssignedCount = AssignedCount + 1, UpdatedAt = ? '
                    . 'WHERE AccessoriesID = ? AND organization_id = ? AND Qty > 0',
                    [$timestamp, $accessory['AccessoriesID'], $this->organizationId()]
                );
                $this->execute(
                    'INSERT INTO accessories_assignments '
                    . '(organization_id, EmployeeID, AccessoriesID, PRNumber, created_at) VALUES (?, ?, ?, ?, ?)',
                    [$this->organizationId(), $employeeId, $accessory['AccessoriesID'], $accessory['PRNumber'], $timestamp]
                );
            }
            return $warnings;
        });
    }

    public function removeStaged(int $accessoryId): int
    {
        return $this->execute('DELETE FROM accessories_temp WHERE AccessoriesID = ? AND organization_id = ?', [$accessoryId, $this->organizationId()]);
    }

    public function returnAssignment(int $accessoryId, string $employeeId, string $timestamp): bool
    {
        return $this->transaction(function () use ($accessoryId, $employeeId, $timestamp): bool {
            $updated = $this->execute(
                "UPDATE accessories_assignments SET Status = 'Returned', updated_at = ? "
                . "WHERE AccessoriesID = ? AND EmployeeID = ? AND organization_id = ? AND LOWER(Status) = 'assigned'",
                [$timestamp, $accessoryId, $employeeId, $this->organizationId()]
            );
            if ($updated === 0) {
                return false;
            }
            $this->execute(
                'UPDATE accessories SET Qty = Qty + ?, '
                . 'AssignedCount = GREATEST(AssignedCount - ?, 0), UpdatedAt = ? WHERE AccessoriesID = ? AND organization_id = ?',
                [$updated, $updated, $timestamp, $accessoryId, $this->organizationId()]
            );
            return true;
        });
    }

    public function markDefective(int $accessoryId, int $quantity, string $prNumber, string $name, string $brand, string $timestamp): bool
    {
        return $this->transaction(function () use ($accessoryId, $quantity, $prNumber, $name, $brand, $timestamp): bool {
            $stock = $this->scalar('SELECT Qty FROM accessories WHERE AccessoriesID = ? AND organization_id = ? FOR UPDATE', [$accessoryId, $this->organizationId()]);
            if ($stock === false || $quantity > (int) $stock) {
                return false;
            }

            return $this->execute(
                'UPDATE accessories SET Qty = Qty - ?, DefectiveCount = DefectiveCount + ?, UpdatedAt = ? '
                . 'WHERE AccessoriesID = ? AND PRNumber = ? AND AccessoriesName = ? AND Brand = ? AND organization_id = ?',
                [$quantity, $quantity, $timestamp, $accessoryId, $prNumber, $name, $brand, $this->organizationId()]
            ) > 0;
        });
    }
}
