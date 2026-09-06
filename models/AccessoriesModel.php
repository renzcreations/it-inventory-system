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
            LEFT JOIN accessories_assignments aa ON ac.AccessoriesID = aa.AccessoriesID
            LEFT JOIN employees e ON e.EmployeeID = aa.EmployeeID
            ORDER BY ac.AccessoriesName ASC, assignmentStatus ASC
            SQL);
    }

    public function allAccessories(): array
    {
        return $this->all('SELECT * FROM accessories');
    }

    public function stagedAccessories(): array
    {
        return $this->all('SELECT * FROM accessories_temp');
    }

    public function accessoryNames(): array
    {
        return $this->all('SELECT AccessoriesName FROM accessories GROUP BY AccessoriesName');
    }

    public function returnHistory(): array
    {
        return $this->all(<<<'SQL'
            SELECT ac.AccessoriesID, ac.Brand, ac.PRNumber, ac.AccessoriesName,
                   aa.id AS assignmentID, aa.Status,
                   e.FirstName, e.LastName, e.EmployeeID
            FROM accessories_assignments aa
            LEFT JOIN accessories ac ON aa.AccessoriesID = ac.AccessoriesID
            LEFT JOIN employees e ON e.EmployeeID = aa.EmployeeID
            WHERE aa.Status = 'Returned'
            ORDER BY ac.AccessoriesName ASC, aa.Status ASC
            SQL);
    }

    public function returnedAccessoryNames(): array
    {
        return $this->all(<<<'SQL'
            SELECT ac.AccessoriesName, aa.Status
            FROM accessories ac
            INNER JOIN accessories_assignments aa ON aa.AccessoriesID = ac.AccessoriesID
            WHERE aa.Status = 'Returned'
            GROUP BY ac.AccessoriesName, aa.Status
            SQL);
    }

    public function returnedAccessories(): array
    {
        return $this->all(<<<'SQL'
            SELECT ac.*, aa.Status
            FROM accessories ac
            INNER JOIN accessories_assignments aa ON aa.AccessoriesID = ac.AccessoriesID
            WHERE aa.Status = 'Returned'
            SQL);
    }

    public function stage(array $accessory): void
    {
        $this->transaction(function () use ($accessory): void {
            $row = $this->first(
                'SELECT AccessoriesID, Qty FROM accessories_temp '
                . 'WHERE AccessoriesName = ? AND Brand <=> ? AND PRNumber <=> ?',
                [$accessory['AccessoriesName'], $accessory['Brand'], $accessory['PRNumber']]
            );

            if ($row) {
                $this->execute(
                    'UPDATE accessories_temp SET Qty = ?, CreatedAt = ? WHERE AccessoriesID = ?',
                    [(int) $row['Qty'] + $accessory['Qty'], $accessory['CreatedAt'], $row['AccessoriesID']]
                );
                return;
            }

            $this->execute(
                'INSERT INTO accessories_temp (AccessoriesName, Brand, Qty, PRNumber, CreatedAt) '
                . 'VALUES (?, ?, ?, ?, ?)',
                [$accessory['AccessoriesName'], $accessory['Brand'], $accessory['Qty'], $accessory['PRNumber'], $accessory['CreatedAt']]
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
                    . 'WHERE AccessoriesName = ? AND Brand <=> ? AND PRNumber <=> ?',
                    [$row['AccessoriesName'], $row['Brand'], $row['PRNumber']]
                );

                if ($existing) {
                    $this->execute(
                        'UPDATE accessories SET Qty = ?, CreatedAt = ? WHERE AccessoriesID = ?',
                        [(int) $existing['Qty'] + (int) $row['Qty'], $createdAt, $existing['AccessoriesID']]
                    );
                } else {
                    $this->execute(
                        'INSERT INTO accessories (AccessoriesName, Brand, Qty, PRNumber, CreatedAt) '
                        . 'VALUES (?, ?, ?, ?, ?)',
                        [$row['AccessoriesName'], $row['Brand'], $row['Qty'], $row['PRNumber'], $createdAt]
                    );
                }
            }

            if ($rows !== []) {
                $this->execute('TRUNCATE TABLE accessories_temp');
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
                    . 'WHERE AccessoriesName = ? AND PRNumber = ?',
                    [$accessoryName, $identifier]
                ) ?? $this->first(
                    'SELECT AccessoriesID, Qty, PRNumber FROM accessories WHERE AccessoriesID = ?',
                    [$identifier]
                );

                if (!$accessory || (int) $accessory['Qty'] <= 0) {
                    $warnings[] = "No available stock for '{$accessoryName}'.";
                    continue;
                }

                $alreadyAssigned = (int) $this->scalar(
                    "SELECT COUNT(*) FROM accessories_assignments "
                    . "WHERE EmployeeID = ? AND AccessoriesID = ? AND Status <> 'Returned'",
                    [$employeeId, $accessory['AccessoriesID']]
                );
                if ($alreadyAssigned > 0) {
                    $warnings[] = "Employee {$employeeId} already has '{$accessoryName}' assigned.";
                    continue;
                }

                $this->execute(
                    'UPDATE accessories SET Qty = Qty - 1, AssignedCount = AssignedCount + 1, UpdatedAt = ? '
                    . 'WHERE AccessoriesID = ? AND Qty > 0',
                    [$timestamp, $accessory['AccessoriesID']]
                );
                $this->execute(
                    'INSERT INTO accessories_assignments '
                    . '(EmployeeID, AccessoriesID, PRNumber, created_at) VALUES (?, ?, ?, ?)',
                    [$employeeId, $accessory['AccessoriesID'], $accessory['PRNumber'], $timestamp]
                );
            }
            return $warnings;
        });
    }

    public function removeStaged(int $accessoryId): int
    {
        return $this->execute('DELETE FROM accessories_temp WHERE AccessoriesID = ?', [$accessoryId]);
    }

    public function returnAssignment(int $accessoryId, string $employeeId, string $timestamp): bool
    {
        return $this->transaction(function () use ($accessoryId, $employeeId, $timestamp): bool {
            $updated = $this->execute(
                "UPDATE accessories_assignments SET Status = 'Returned', updated_at = ? "
                . "WHERE AccessoriesID = ? AND EmployeeID = ? AND Status = 'Assigned'",
                [$timestamp, $accessoryId, $employeeId]
            );
            if ($updated === 0) {
                return false;
            }
            $this->execute(
                'UPDATE accessories SET Qty = Qty + ?, '
                . 'AssignedCount = GREATEST(AssignedCount - ?, 0), UpdatedAt = ? WHERE AccessoriesID = ?',
                [$updated, $updated, $timestamp, $accessoryId]
            );
            return true;
        });
    }

    public function markDefective(int $accessoryId, int $quantity, string $prNumber, string $name, string $brand, string $timestamp): bool
    {
        return $this->transaction(function () use ($accessoryId, $quantity, $prNumber, $name, $brand, $timestamp): bool {
            $stock = $this->scalar('SELECT Qty FROM accessories WHERE AccessoriesID = ? FOR UPDATE', [$accessoryId]);
            if ($stock === false || $quantity > (int) $stock) {
                return false;
            }

            return $this->execute(
                'UPDATE accessories SET Qty = Qty - ?, DefectiveCount = DefectiveCount + ?, UpdatedAt = ? '
                . 'WHERE AccessoriesID = ? AND PRNumber = ? AND AccessoriesName = ? AND Brand = ?',
                [$quantity, $quantity, $timestamp, $accessoryId, $prNumber, $name, $brand]
            ) > 0;
        });
    }
}
