<?php

namespace Models;

use System\Core\Model;

class PartsModel extends Model
{
    public function excludedPartTypes(array $partTypes, string $table = 'temp_update_pc_parts'): array
    {
        $allowedTables = ['temp_update_pc_parts', 'temp_pc_parts'];
        if (!in_array($table, $allowedTables, true) || $partTypes === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($partTypes), '?'));
        return array_column($this->all(
            "SELECT DISTINCT PartType FROM {$table} WHERE organization_id = ? AND PartType IN ({$placeholders})",
            array_merge([$this->organizationId()], $partTypes)
        ), 'PartType');
    }

    public function allParts(): array
    {
        return $this->all('SELECT * FROM parts WHERE organization_id = ? ORDER BY Status ASC', [$this->organizationId()]);
    }

    public function partTypes(): array
    {
        return $this->all('SELECT DISTINCT PartType FROM parts WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function temporaryUpdateParts(): array
    {
        return $this->all('SELECT * FROM temp_update_pc_parts WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function temporaryComputer(): ?array
    {
        return $this->first('SELECT * FROM temp_pc WHERE organization_id = ? LIMIT 1', [$this->organizationId()]);
    }

    public function stagedParts(): array
    {
        return $this->all('SELECT * FROM temporary_storage_parts WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function partsWithCurrentHistory(): array
    {
        return $this->all(<<<'SQL'
            SELECT p.*,
                   p.Status AS PartStatus,
                   e.FirstName,
                   e.LastName,
                   ph.Status AS HistoryStatus,
                   pc.PCName,
                   pp.PartID AS PartsIdentification,
                   pp.PCID
            FROM parts p
            LEFT JOIN parts_history ph
                ON ph.PartID = p.PartID
                AND ph.organization_id = p.organization_id
                AND ph.id = (SELECT MAX(id) FROM parts_history WHERE PartID = p.PartID AND organization_id = p.organization_id)
            LEFT JOIN pc_parts pp ON pp.PartID = p.PartID AND pp.organization_id = p.organization_id
            LEFT JOIN pcs pc ON pc.PCID = pp.PCID AND pc.organization_id = p.organization_id
            LEFT JOIN employees e ON e.EmployeeID = ph.EmployeeID AND e.organization_id = p.organization_id
            WHERE p.organization_id = ?
            ORDER BY CASE WHEN ph.Status = 'Returned' THEN 1 ELSE 0 END ASC,
                     PartStatus ASC
            SQL, [$this->organizationId()]);
    }

    public function availableParts(array $excludedTypes = []): array
    {
        $conditions = [
            'p.organization_id = ?',
            "LOWER(p.Status) = 'available'",
            'p.PartID NOT IN (SELECT PartID FROM temp_update_pc_parts WHERE organization_id = ?)',
        ];
        $parameters = [$this->organizationId(), $this->organizationId()];

        if ($excludedTypes !== []) {
            $conditions[] = 'p.PartType NOT IN (' . implode(',', array_fill(0, count($excludedTypes), '?')) . ')';
            $parameters = array_merge($parameters, $excludedTypes);
        }

        return $this->all(
            'SELECT p.*, COALESCE(e.FirstName, \'Unassigned\') AS FirstName, '
            . "COALESCE(e.LastName, '') AS LastName "
            . 'FROM parts p '
            . 'LEFT JOIN (SELECT ph.PartID, ph.EmployeeID, ph.created_at, ph.updated_at '
            . 'FROM parts_history ph INNER JOIN '
            . '(SELECT organization_id, PartID, MAX(created_at) AS latest FROM parts_history GROUP BY organization_id, PartID) latest_ph '
            . 'ON ph.organization_id = latest_ph.organization_id AND ph.PartID = latest_ph.PartID AND ph.created_at = latest_ph.latest) latest '
            . 'ON p.organization_id = latest.organization_id AND p.PartID = latest.PartID '
            . 'LEFT JOIN employees e ON latest.EmployeeID = e.EmployeeID AND e.organization_id = p.organization_id '
            . 'WHERE ' . implode(' AND ', $conditions),
            $parameters
        );
    }

    public function serialExistsInInventoryOrStage(string $serialNumber): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM parts WHERE organization_id = ? AND SerialNumber = ?) '
            . 'OR EXISTS(SELECT 1 FROM temporary_storage_parts WHERE organization_id = ? AND SerialNumber = ?)',
            [$this->organizationId(), $serialNumber, $this->organizationId(), $serialNumber]
        );
    }

    public function serialExists(string $partType, string $serialNumber): bool
    {
        return (bool) $this->scalar(
            'SELECT EXISTS(SELECT 1 FROM parts WHERE organization_id = ? AND PartType = ? AND SerialNumber = ?)',
            [$this->organizationId(), $partType, $serialNumber]
        );
    }

    public function nextPartNumber(string $cleanedPartType, string $partType): int
    {
        $pattern = '^' . preg_quote($cleanedPartType, '/') . '[0-9]+$';
        return (int) ($this->scalar(
            'SELECT MAX(CAST(SUBSTRING(uniqueID, LENGTH(?) + 1) AS UNSIGNED)) '
            . 'FROM parts WHERE organization_id = ? AND PartType = ? AND uniqueID REGEXP ?',
            [$cleanedPartType, $this->organizationId(), $partType, $pattern]
        ) ?: 0) + 1;
    }

    public function stagePart(array $part): void
    {
        $this->execute(
            'INSERT INTO temporary_storage_parts '
            . '(organization_id, PRNumber, PartType, Brand, Model, SerialNumber, created_at) VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$this->organizationId(), $part['PRNumber'], $part['PartType'], $part['Brand'], $part['Model'], $part['SerialNumber'], $part['created_at']]
        );
    }

    public function createPart(array $part): int
    {
        $this->execute(
            'INSERT INTO parts (organization_id, uniqueID, PartType, Brand, Model, SerialNumber, PRNumber, created_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
            [$this->organizationId(), $part['uniqueID'], $part['PartType'], $part['Brand'], $part['Model'], $part['SerialNumber'], $part['PRNumber'], $part['created_at']]
        );
        return $this->lastInsertId();
    }

    public function clearStagedParts(): void
    {
        $this->execute('DELETE FROM temporary_storage_parts WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function find(int $partId): ?array
    {
        return $this->first('SELECT * FROM parts WHERE PartID = ? AND organization_id = ?', [$partId, $this->organizationId()]);
    }

    public function updatePart(int $partId, array $attributes, string $updatedAt): void
    {
        $allowed = ['uniqueID', 'Brand', 'Model', 'SerialNumber'];
        $sets = [];
        $parameters = [];

        foreach ($allowed as $column) {
            if (array_key_exists($column, $attributes) && $attributes[$column] !== '') {
                $sets[] = "{$column} = ?";
                $parameters[] = $attributes[$column];
            }
        }

        $sets[] = 'updated_at = ?';
        $parameters[] = $updatedAt;
        $parameters[] = $partId;
        $parameters[] = $this->organizationId();
        $this->execute('UPDATE parts SET ' . implode(', ', $sets) . ' WHERE PartID = ? AND organization_id = ?', $parameters);
    }

    public function updateStatus(int $partId, string $status, string $updatedAt): int
    {
        return $this->execute(
            'UPDATE parts SET Status = ?, updated_at = ? WHERE PartID = ? AND organization_id = ?',
            [$status, $updatedAt, $partId, $this->organizationId()]
        );
    }
}
