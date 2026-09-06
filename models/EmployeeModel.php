<?php

namespace Models;

use System\Core\Model;

class EmployeeModel extends Model
{
    public function allEmployees(): array
    {
        return $this->all(
            'SELECT *, (Signature IS NOT NULL) AS HasSignature FROM employees WHERE organization_id = ? ORDER BY Status ASC',
            [$this->organizationId()]
        );
    }

    public function departments(): array
    {
        return $this->all('SELECT Department FROM employees WHERE organization_id = ? GROUP BY Department', [$this->organizationId()]);
    }

    public function employeeIdExists(string $employeeId): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM employees WHERE organization_id = ? AND EmployeeID = ?)',
            [$this->organizationId(), $employeeId]
        );
    }

    public function createEmployee(array $employee): int
    {
        $columns = ['organization_id', 'EmployeeID', 'FirstName', 'LastName', 'Email', 'Department', 'JobTitle', 'created_at'];
        $values = [
            $this->organizationId(), $employee['EmployeeID'], $employee['FirstName'], $employee['LastName'],
            $employee['Email'], $employee['Department'], $employee['JobTitle'] ?? null, $employee['created_at'],
        ];

        if (isset($employee['WorkStatus'])) {
            array_splice($columns, 7, 0, ['WorkStatus']);
            array_splice($values, 7, 0, [$employee['WorkStatus']]);
        } elseif (isset($employee['Status'])) {
            array_splice($columns, 7, 0, ['Status']);
            array_splice($values, 7, 0, [$employee['Status']]);
        }

        $this->execute(
            'INSERT INTO employees (' . implode(', ', $columns) . ') VALUES ('
            . implode(', ', array_fill(0, count($columns), '?')) . ')',
            $values
        );
        return $this->lastInsertId();
    }

    public function findByInternalId(int $id): ?array
    {
        return $this->first('SELECT * FROM employees WHERE id = ? AND organization_id = ?', [$id, $this->organizationId()]);
    }

    public function findByEmployeeId(string $employeeId): ?array
    {
        return $this->first('SELECT * FROM employees WHERE EmployeeID = ? AND organization_id = ?', [$employeeId, $this->organizationId()]);
    }

    public function updateEmployee(int $id, array $attributes, string $updatedAt): void
    {
        $this->transaction(function () use ($id, $attributes, $updatedAt): void {
            $current = $this->findByInternalId($id);
            if (!$current) {
                throw new \RuntimeException('Employee not found.');
            }

            $allowed = ['EmployeeID', 'FirstName', 'LastName', 'Email', 'Department', 'JobTitle', 'WorkStatus'];
            $sets = [];
            $parameters = [];
            foreach ($allowed as $column) {
                if (isset($attributes[$column]) && $attributes[$column] !== '') {
                    $sets[] = "{$column} = ?";
                    $parameters[] = $attributes[$column];
                }
            }
            $sets[] = 'updated_at = ?';
            $parameters[] = $updatedAt;
            $parameters[] = $id;
            $parameters[] = $this->organizationId();
            $this->execute('UPDATE employees SET ' . implode(', ', $sets) . ' WHERE id = ? AND organization_id = ?', $parameters);

            $newEmployeeId = $attributes['EmployeeID'] ?? $current['EmployeeID'];
            if ($newEmployeeId !== $current['EmployeeID']) {
                foreach (['parts_history', 'assignments', 'accessories_assignments', 'returned_custody', 'temp_assignments'] as $table) {
                    $this->execute(
                        "UPDATE {$table} SET EmployeeID = ? WHERE EmployeeID = ? AND organization_id = ?",
                        [$newEmployeeId, $current['EmployeeID'], $this->organizationId()]
                    );
                }
            }
        });
    }

    public function resign(string $employeeId, string $status, string $timestamp): void
    {
        $this->transaction(function () use ($employeeId, $status, $timestamp): void {
            $computer = $this->first(<<<'SQL'
                SELECT pc.PCID, pc.PCName
                FROM pcs pc
                INNER JOIN assignments a ON a.PCID = pc.PCID
                WHERE a.EmployeeID = ? AND a.organization_id = ? AND pc.organization_id = a.organization_id AND LOWER(a.Status) = 'assigned'
                ORDER BY a.AssignedDate DESC
                LIMIT 1
                SQL, [$employeeId, $this->organizationId()]);

            if ($computer) {
                $this->returnAssets($employeeId, (int) $computer['PCID'], $computer['PCName'], $timestamp);
            }

            $this->execute(
                'UPDATE employees SET Status = ?, updated_at = ? WHERE EmployeeID = ? AND organization_id = ?',
                [$status, $timestamp, $employeeId, $this->organizationId()]
            );
        });
    }

    public function activeAccessoryIds(string $employeeId): array
    {
        return array_column($this->all(
            "SELECT AccessoriesID FROM accessories_assignments WHERE EmployeeID = ? AND organization_id = ? AND LOWER(Status) = 'assigned'",
            [$employeeId, $this->organizationId()]
        ), 'AccessoriesID');
    }

    public function accessoriesByIds(array $ids): array
    {
        if ($ids === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        return $this->all(
            "SELECT AccessoriesName, AccessoriesID, PRNumber, Brand FROM accessories "
            . "WHERE organization_id = ? AND AccessoriesID IN ({$placeholders})",
            array_merge([$this->organizationId()], array_values($ids))
        );
    }

    public function activeComputerAssignment(string $employeeId): ?array
    {
        return $this->first(
            "SELECT PCID, created_at FROM assignments WHERE EmployeeID = ? AND organization_id = ? AND LOWER(Status) = 'assigned' "
            . 'ORDER BY AssignedDate DESC LIMIT 1',
            [$employeeId, $this->organizationId()]
        );
    }

    public function computerName(int $pcId): ?string
    {
        $value = $this->scalar('SELECT PCName FROM pcs WHERE PCID = ? AND organization_id = ?', [$pcId, $this->organizationId()]);
        return $value === false ? null : (string) $value;
    }

    public function computerParts(int $pcId): array
    {
        return $this->all(<<<'SQL'
            SELECT p.PartType, p.Brand, p.Model, p.SerialNumber, p.uniqueID
            FROM pc_parts pp
            INNER JOIN parts p ON pp.PartID = p.PartID
            WHERE pp.PCID = ? AND pp.organization_id = ? AND p.organization_id = pp.organization_id
            SQL, [$pcId, $this->organizationId()]);
    }

    public function companyDetails(): array
    {
        return $this->all('SELECT * FROM company_details WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function administrator(): ?array
    {
        return $this->first("SELECT * FROM users WHERE organization_id = ? AND type = 'Administrator' LIMIT 1", [$this->organizationId()]);
    }

    public function availableAccessories(): array
    {
        return $this->all(
            'SELECT AccessoriesName, AccessoriesID, Brand, PRNumber, Qty FROM accessories WHERE organization_id = ? AND Qty > 0',
            [$this->organizationId()]
        );
    }

    public function matchesIdentity(string $employeeId, string $email): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM employees WHERE organization_id = ? AND EmployeeID = ? AND Email = ?)',
            [$this->organizationId(), $employeeId, $email]
        );
    }

    public function updateSignature(string $employeeId, string $path, string $timestamp): void
    {
        $this->execute(
            'UPDATE employees SET Signature = ?, signature_upload_date = ?, updated_at = ? WHERE EmployeeID = ? AND organization_id = ?',
            [$path, $timestamp, $timestamp, $employeeId, $this->organizationId()]
        );
    }

    public function authenticate(string $email, string $employeeId): ?array
    {
        return $this->first(
            'SELECT * FROM employees WHERE Email = ? AND EmployeeID = ? AND organization_id = ?',
            [$email, $employeeId, $this->organizationId()]
        );
    }

    private function returnAssets(string $employeeId, int $pcId, string $pcName, string $timestamp): void
    {
        $parts = $this->all(<<<'SQL'
            SELECT DISTINCT pp.PartID
            FROM pc_parts pp
            INNER JOIN parts_history ph ON ph.PartID = pp.PartID
            WHERE ph.EmployeeID = ? AND pp.PCID = ? AND ph.organization_id = ?
              AND pp.organization_id = ph.organization_id
            SQL, [$employeeId, $pcId, $this->organizationId()]);

        $accessories = $this->all(<<<'SQL'
            SELECT DISTINCT a.AccessoriesID, a.PRNumber, a.AccessoriesName
            FROM accessories_assignments aa
            INNER JOIN accessories a ON a.AccessoriesID = aa.AccessoriesID
            WHERE aa.EmployeeID = ? AND aa.organization_id = ? AND LOWER(aa.Status) = 'assigned'
              AND a.organization_id = aa.organization_id
            SQL, [$employeeId, $this->organizationId()]);

        foreach ($parts as $part) {
            $this->execute(
                'INSERT INTO returned_custody (organization_id, EmployeeID, PCID, PCName, PartID, created_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?)',
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
        $this->execute(
            "UPDATE pcs SET Status = 'Returned', updated_at = ? WHERE PCID = ? AND organization_id = ?",
            [$timestamp, $pcId, $this->organizationId()]
        );
        $this->execute(
            "UPDATE parts_history SET Status = 'Returned', updated_at = ? "
            . "WHERE EmployeeID = ? AND organization_id = ? AND LOWER(Status) = 'assigned'",
            [$timestamp, $employeeId, $this->organizationId()]
        );

        if ($accessories !== []) {
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
}
