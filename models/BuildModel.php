<?php

namespace Models;

use System\Core\Model;

class BuildModel extends Model
{
    public function stagedParts(): array
    {
        return $this->all('SELECT * FROM temp_pc_parts WHERE organization_id = ?', [$this->organizationId()]);
    }

    public function excludedPartTypes(array $types): array
    {
        if ($types === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        return array_column($this->all(
            "SELECT DISTINCT PartType FROM temp_pc_parts WHERE organization_id = ? AND PartType IN ({$placeholders})",
            array_merge([$this->organizationId()], $types)
        ), 'PartType');
    }

    public function availableParts(array $excludedTypes): array
    {
        $sql = "SELECT * FROM parts WHERE organization_id = ? AND LOWER(Status) = 'available' "
            . 'AND PartID NOT IN (SELECT PartID FROM temp_pc_parts WHERE organization_id = ?)';
        $parameters = [$this->organizationId(), $this->organizationId()];
        if ($excludedTypes !== []) {
            $sql .= ' AND PartType NOT IN (' . implode(',', array_fill(0, count($excludedTypes), '?')) . ')';
            $parameters = array_merge($parameters, $excludedTypes);
        }
        return $this->all($sql . ' ORDER BY created_at DESC', $parameters);
    }

    public function availablePartTypeDates(array $excludedTypes): array
    {
        $sql = "SELECT PartType, MAX(created_at) AS latest_date FROM parts WHERE organization_id = ? AND LOWER(Status) = 'available'";
        $parameters = [$this->organizationId()];
        if ($excludedTypes !== []) {
            $sql .= ' AND PartType NOT IN (' . implode(',', array_fill(0, count($excludedTypes), '?')) . ')';
            $parameters = array_merge($parameters, $excludedTypes);
        }
        return $this->all($sql . ' GROUP BY PartType', $parameters);
    }

    public function availablePartTypes(array $excludedTypes): array
    {
        $sql = "SELECT DISTINCT PartType FROM parts WHERE organization_id = ? AND LOWER(Status) = 'available'";
        $parameters = [$this->organizationId()];
        if ($excludedTypes !== []) {
            $sql .= ' AND PartType NOT IN (' . implode(',', array_fill(0, count($excludedTypes), '?')) . ')';
            $parameters = array_merge($parameters, $excludedTypes);
        }
        return $this->all($sql, $parameters);
    }

    public function stagePart(array $part): void
    {
        $this->execute(
            'INSERT INTO temp_pc_parts (organization_id, PartID, PartType, Brand, Model, SerialNumber, created_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?, ?)',
            [$this->organizationId(), $part['PartID'], $part['PartType'], $part['Brand'], $part['Model'], $part['SerialNumber'], $part['created_at']]
        );
    }

    public function computerNameExists(string $name): bool
    {
        return (bool) $this->scalar('SELECT EXISTS(SELECT 1 FROM pcs WHERE organization_id = ? AND PCName = ?)', [$this->organizationId(), $name]);
    }

    public function buildComputer(string $name, string $category, array $partIds, string $createdAt): int
    {
        return $this->transaction(function () use ($name, $category, $partIds, $createdAt): int {
            $organizationId = $this->organizationId();
            $this->execute('INSERT INTO pcs (organization_id, PCName, Category, created_at) VALUES (?, ?, ?, ?)', [$organizationId, $name, $category, $createdAt]);
            $pcId = $this->lastInsertId();

            foreach ($partIds as $partId) {
                $this->execute(
                    'INSERT INTO pc_parts (organization_id, PCID, PartID, created_at) VALUES (?, ?, ?, ?)',
                    [$organizationId, $pcId, $partId, $createdAt]
                );
                $this->execute(
                    "UPDATE parts SET Status = 'In Use', updated_at = ? WHERE PartID = ? AND organization_id = ?",
                    [$createdAt, $partId, $organizationId]
                );
            }

            $this->execute('DELETE FROM temp_pc_parts WHERE organization_id = ?', [$organizationId]);
            return $pcId;
        });
    }

    public function removeStagedPart(int $partId): int
    {
        return $this->execute('DELETE FROM temp_pc_parts WHERE PartID = ? AND organization_id = ?', [$partId, $this->organizationId()]);
    }
}
