<?php

namespace Models;

use System\Core\Model;

class BuildModel extends Model
{
    public function stagedParts(): array
    {
        return $this->all('SELECT * FROM temp_pc_parts');
    }

    public function excludedPartTypes(array $types): array
    {
        if ($types === []) {
            return [];
        }
        $placeholders = implode(',', array_fill(0, count($types), '?'));
        return array_column($this->all(
            "SELECT DISTINCT PartType FROM temp_pc_parts WHERE PartType IN ({$placeholders})",
            $types
        ), 'PartType');
    }

    public function availableParts(array $excludedTypes): array
    {
        $sql = "SELECT * FROM parts WHERE Status = 'Available' "
            . 'AND PartID NOT IN (SELECT PartID FROM temp_pc_parts)';
        $parameters = [];
        if ($excludedTypes !== []) {
            $sql .= ' AND PartType NOT IN (' . implode(',', array_fill(0, count($excludedTypes), '?')) . ')';
            $parameters = $excludedTypes;
        }
        return $this->all($sql . ' ORDER BY created_at DESC', $parameters);
    }

    public function availablePartTypeDates(array $excludedTypes): array
    {
        $sql = "SELECT PartType, MAX(created_at) AS latest_date FROM parts WHERE Status = 'Available'";
        $parameters = [];
        if ($excludedTypes !== []) {
            $sql .= ' AND PartType NOT IN (' . implode(',', array_fill(0, count($excludedTypes), '?')) . ')';
            $parameters = $excludedTypes;
        }
        return $this->all($sql . ' GROUP BY PartType', $parameters);
    }

    public function availablePartTypes(array $excludedTypes): array
    {
        $sql = "SELECT DISTINCT PartType FROM parts WHERE Status = 'Available'";
        $parameters = [];
        if ($excludedTypes !== []) {
            $sql .= ' AND PartType NOT IN (' . implode(',', array_fill(0, count($excludedTypes), '?')) . ')';
            $parameters = $excludedTypes;
        }
        return $this->all($sql, $parameters);
    }

    public function stagePart(array $part): void
    {
        $this->execute(
            'INSERT INTO temp_pc_parts (PartID, PartType, Brand, Model, SerialNumber, created_at) '
            . 'VALUES (?, ?, ?, ?, ?, ?)',
            [$part['PartID'], $part['PartType'], $part['Brand'], $part['Model'], $part['SerialNumber'], $part['created_at']]
        );
    }

    public function computerNameExists(string $name): bool
    {
        return (bool) $this->scalar('SELECT EXISTS(SELECT 1 FROM pcs WHERE PCName = ?)', [$name]);
    }

    public function buildComputer(string $name, array $partIds, string $createdAt): int
    {
        return $this->transaction(function () use ($name, $partIds, $createdAt): int {
            $this->execute('INSERT INTO pcs (PCName, created_at) VALUES (?, ?)', [$name, $createdAt]);
            $pcId = $this->lastInsertId();

            foreach ($partIds as $partId) {
                $this->execute(
                    'INSERT INTO pc_parts (PCID, PartID, created_at) VALUES (?, ?, ?)',
                    [$pcId, $partId, $createdAt]
                );
                $this->execute(
                    "UPDATE parts SET Status = 'In Use', updated_at = ? WHERE PartID = ?",
                    [$createdAt, $partId]
                );
            }

            $this->execute('TRUNCATE TABLE temp_pc_parts');
            return $pcId;
        });
    }

    public function removeStagedPart(int $partId): int
    {
        return $this->execute('DELETE FROM temp_pc_parts WHERE PartID = ?', [$partId]);
    }
}
