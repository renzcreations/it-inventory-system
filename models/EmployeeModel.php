<?php

namespace Models;

use System\Core\Model;

class EmployeeModel extends Model
{
    public function allEmployees(): array
    {
        return $this->all(
            'SELECT *, Status AS WorkStatus, (Signature IS NOT NULL) AS HasSignature '
            . 'FROM employees ORDER BY Status ASC'
        );
    }

    public function departments(): array
    {
        return $this->all('SELECT Department FROM employees GROUP BY Department');
    }

    public function employeeIdExists(string $employeeId): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM employees WHERE EmployeeID = ?)',
            [$employeeId]
        );
    }

    public function createEmployee(array $employee): int
    {
        $columns = ['EmployeeID', 'FirstName', 'LastName', 'Email', 'Department', 'created_at'];
        $values = [
            $employee['EmployeeID'], $employee['FirstName'], $employee['LastName'],
            $employee['Email'], $employee['Department'], $employee['created_at'],
        ];

        if (isset($employee['WorkStatus'])) {
            array_splice($columns, 5, 0, ['WorkStatus']);
            array_splice($values, 5, 0, [$employee['WorkStatus']]);
        } elseif (isset($employee['Status'])) {
            array_splice($columns, 5, 0, ['Status']);
            array_splice($values, 5, 0, [$employee['Status']]);
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
        return $this->first('SELECT * FROM employees WHERE id = ?', [$id]);
    }

    public function findByEmployeeId(string $employeeId): ?array
    {
        return $this->first('SELECT * FROM employees WHERE EmployeeID = ?', [$employeeId]);
    }

    public function updateEmployee(int $id, array $attributes, string $updatedAt): void
    {
        $this->transaction(function () use ($id, $attributes, $updatedAt): void {
            $current = $this->findByInternalId($id);
            if (!$current) {
                throw new \RuntimeException('Employee not found.');
            }

            $allowed = ['EmployeeID', 'FirstName', 'LastName', 'Email', 'Department'];
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
            $this->execute('UPDATE employees SET ' . implode(', ', $sets) . ' WHERE id = ?', $parameters);

            $newEmployeeId = $attributes['EmployeeID'] ?? $current['EmployeeID'];
            if ($newEmployeeId !== $current['EmployeeID']) {
                foreach (['parts_history', 'assignments', 'accessories_assignments', 'returned_custody', 'temp_assignments'] as $table) {
                    $this->execute(
                        "UPDATE {$table} SET EmployeeID = ? WHERE EmployeeID = ?",
                        [$newEmployeeId, $current['EmployeeID']]
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
                WHERE a.EmployeeID = ? AND a.Status = 'Assigned'
                ORDER BY a.AssignedDate DESC
                LIMIT 1
                SQL, [$employeeId]);

            if ($computer) {
                $this->returnAssets($employeeId, (int) $computer['PCID'], $computer['PCName'], $timestamp);
            }

            $this->execute(
                'UPDATE employees SET Status = ?, updated_at = ? WHERE EmployeeID = ?',
                [$status, $timestamp, $employeeId]
            );
        });
    }

    public function activeAccessoryIds(string $employeeId): array
    {
        return array_column($this->all(
            "SELECT AccessoriesID FROM accessories_assignments WHERE EmployeeID = ? AND Status = 'Assigned'",
            [$employeeId]
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
            . "WHERE AccessoriesID IN ({$placeholders})",
            array_values($ids)
        );
    }

    public function activeComputerAssignment(string $employeeId): ?array
    {
        return $this->first(
            "SELECT PCID, created_at FROM assignments WHERE EmployeeID = ? AND Status = 'Assigned' "
            . 'ORDER BY AssignedDate DESC LIMIT 1',
            [$employeeId]
        );
    }

    public function computerName(int $pcId): ?string
    {
        $value = $this->scalar('SELECT PCName FROM pcs WHERE PCID = ?', [$pcId]);
        return $value === false ? null : (string) $value;
    }

    public function computerParts(int $pcId): array
    {
        return $this->all(<<<'SQL'
            SELECT p.PartType, p.Brand, p.Model, p.SerialNumber, p.uniqueID
            FROM pc_parts pp
            INNER JOIN parts p ON pp.PartID = p.PartID
            WHERE pp.PCID = ?
            SQL, [$pcId]);
    }

    public function companyDetails(): array
    {
        return $this->all('SELECT * FROM company_details');
    }

    public function administrator(): ?array
    {
        return $this->first("SELECT * FROM users WHERE type = 'Administrator' LIMIT 1");
    }

    public function availableAccessories(): array
    {
        return $this->all(
            'SELECT AccessoriesName, AccessoriesID, Brand, PRNumber, Qty FROM accessories WHERE Qty > 0'
        );
    }

    public function matchesIdentity(string $employeeId, string $email): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM employees WHERE EmployeeID = ? AND Email = ?)',
            [$employeeId, $email]
        );
    }

    public function updateSignature(string $employeeId, string $path, string $timestamp): void
    {
        $this->execute(
            'UPDATE employees SET Signature = ?, signature_upload_date = ?, updated_at = ? WHERE EmployeeID = ?',
            [$path, $timestamp, $timestamp, $employeeId]
        );
    }

    public function authenticate(string $email, string $employeeId): ?array
    {
        return $this->first(
            'SELECT * FROM employees WHERE Email = ? AND EmployeeID = ?',
            [$email, $employeeId]
        );
    }

    private function returnAssets(string $employeeId, int $pcId, string $pcName, string $timestamp): void
    {
        $parts = $this->all(<<<'SQL'
            SELECT DISTINCT pp.PartID
            FROM pc_parts pp
            INNER JOIN parts_history ph ON ph.PartID = pp.PartID
            WHERE ph.EmployeeID = ? AND pp.PCID = ?
            SQL, [$employeeId, $pcId]);

        $accessories = $this->all(<<<'SQL'
            SELECT DISTINCT a.AccessoriesID, a.PRNumber, a.AccessoriesName
            FROM accessories_assignments aa
            INNER JOIN accessories a ON a.AccessoriesID = aa.AccessoriesID
            WHERE aa.EmployeeID = ? AND aa.Status = 'Assigned'
            SQL, [$employeeId]);

        foreach ($parts as $part) {
            $this->execute(
                'INSERT INTO returned_custody (EmployeeID, PCID, PCName, PartID, created_at) '
                . 'VALUES (?, ?, ?, ?, ?)',
                [$employeeId, $pcId, $pcName, $part['PartID'], $timestamp]
            );
        }
        foreach ($accessories as $accessory) {
            $this->execute(
                'INSERT INTO returned_custody '
                . '(EmployeeID, PCID, PCName, AccessoriesID, AccessoriesPRNumber, AccessoriesName, created_at) '
                . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
                [$employeeId, $pcId, $pcName, $accessory['AccessoriesID'], $accessory['PRNumber'], $accessory['AccessoriesName'], $timestamp]
            );
        }

        $this->execute(
            "UPDATE assignments SET ReturnedDate = ?, Status = 'Returned', updated_at = ? "
            . "WHERE PCID = ? AND EmployeeID = ? AND Status = 'Assigned'",
            [$timestamp, $timestamp, $pcId, $employeeId]
        );
        $this->execute(
            "UPDATE pcs SET Status = 'Returned', updated_at = ? WHERE PCID = ?",
            [$timestamp, $pcId]
        );
        $this->execute(
            "UPDATE parts_history SET Status = 'Returned', updated_at = ? "
            . "WHERE EmployeeID = ? AND Status = 'Assigned'",
            [$timestamp, $employeeId]
        );

        if ($accessories !== []) {
            $this->execute(
                "UPDATE accessories_assignments SET Status = 'Returned', updated_at = ? "
                . "WHERE EmployeeID = ? AND Status = 'Assigned'",
                [$timestamp, $employeeId]
            );
            foreach ($accessories as $accessory) {
                $this->execute(
                    'UPDATE accessories SET Qty = Qty + 1, AssignedCount = GREATEST(AssignedCount - 1, 0), '
                    . 'UpdatedAt = ? WHERE AccessoriesID = ?',
                    [$timestamp, $accessory['AccessoriesID']]
                );
            }
        }
    }
}
