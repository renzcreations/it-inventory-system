<?php

namespace Models;

use System\Core\Model;

class CatalogModel extends Model
{
    public function groups(): array
    {
        return $this->all(
            'SELECT g.*, COUNT(v.id) AS value_count FROM catalog_groups g '
            . 'LEFT JOIN catalog_values v ON v.group_id = g.id '
            . 'WHERE g.organization_id = ? GROUP BY g.id ORDER BY g.sort_order, g.name',
            [$this->organizationId()]
        );
    }

    public function values(?int $groupId = null, bool $activeOnly = false): array
    {
        $sql = 'SELECT v.*, g.code AS group_code, g.name AS group_name FROM catalog_values v '
            . 'INNER JOIN catalog_groups g ON g.id = v.group_id AND g.organization_id = v.organization_id '
            . 'WHERE v.organization_id = ?';
        $parameters = [$this->organizationId()];
        if ($groupId !== null) {
            $sql .= ' AND v.group_id = ?';
            $parameters[] = $groupId;
        }
        if ($activeOnly) {
            $sql .= ' AND v.is_active = 1';
        }
        return $this->all($sql . ' ORDER BY g.sort_order, v.sort_order, v.name', $parameters);
    }

    public function valuesByGroup(string $groupCode): array
    {
        return $this->all(
            'SELECT v.* FROM catalog_values v INNER JOIN catalog_groups g ON g.id = v.group_id '
            . 'WHERE v.organization_id = ? AND g.organization_id = ? AND g.code = ? AND v.is_active = 1 '
            . 'ORDER BY v.sort_order, v.name',
            [$this->organizationId(), $this->organizationId(), $groupCode]
        );
    }

    public function createGroup(array $data): int
    {
        $this->execute(
            'INSERT INTO catalog_groups (organization_id, code, name, description, entity_type, sort_order) '
            . 'VALUES (?, ?, ?, ?, ?, ?)',
            [$this->organizationId(), $data['code'], $data['name'], $data['description'], $data['entity_type'], $data['sort_order']]
        );
        return $this->lastInsertId();
    }

    public function saveValue(array $data): int
    {
        $organizationId = $this->organizationId();
        if (!empty($data['id'])) {
            $this->execute(
                'UPDATE catalog_values SET name = ?, description = ?, color = ?, sort_order = ?, '
                . 'is_active = ?, updated_at = NOW() WHERE id = ? AND organization_id = ?',
                [$data['name'], $data['description'], $data['color'], $data['sort_order'], $data['is_active'], $data['id'], $organizationId]
            );
            return (int) $data['id'];
        }
        $this->execute(
            'INSERT INTO catalog_values (organization_id, group_id, code, name, description, color, sort_order) '
            . 'SELECT ?, id, ?, ?, ?, ?, ? FROM catalog_groups WHERE id = ? AND organization_id = ?',
            [$organizationId, $data['code'], $data['name'], $data['description'], $data['color'], $data['sort_order'], $data['group_id'], $organizationId]
        );
        return $this->lastInsertId();
    }

    public function deactivateValue(int $id): void
    {
        $this->execute(
            'UPDATE catalog_values SET is_active = 0, updated_at = NOW() WHERE id = ? AND organization_id = ?',
            [$id, $this->organizationId()]
        );
    }

    public function settings(): array
    {
        $rows = $this->all(
            'SELECT setting_key, setting_value FROM app_settings WHERE organization_id = ?',
            [$this->organizationId()]
        );
        return array_column($rows, 'setting_value', 'setting_key');
    }

    public function saveSettings(array $settings): void
    {
        $allowed = ['app.name', 'app.tagline', 'app.primary_color', 'app.timezone', 'app.date_format', 'reports.footer'];
        foreach ($allowed as $key) {
            if (!array_key_exists($key, $settings)) {
                continue;
            }
            $this->execute(
                'UPDATE app_settings SET setting_value = ?, updated_at = NOW() '
                . 'WHERE organization_id = ? AND setting_key = ?',
                [(string) $settings[$key], $this->organizationId(), $key]
            );
        }
    }

    public function navigation(): array
    {
        return $this->all(
            'SELECT * FROM navigation_items WHERE organization_id = ? AND is_active = 1 ORDER BY sort_order, label',
            [$this->organizationId()]
        );
    }
}
