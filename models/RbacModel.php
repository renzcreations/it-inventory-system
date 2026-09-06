<?php

namespace Models;

use System\Core\Model;

class RbacModel extends Model
{
    public function permissionsForUser(int $userId, int $organizationId): array
    {
        return $this->all(
            'SELECT DISTINCT p.slug FROM permissions p '
            . 'INNER JOIN role_permissions rp ON rp.permission_id = p.id '
            . 'INNER JOIN roles r ON r.id = rp.role_id AND r.is_active = 1 '
            . 'INNER JOIN user_roles ur ON ur.role_id = r.id '
            . 'WHERE ur.user_id = ? AND ur.organization_id = ? AND r.organization_id = ?',
            [$userId, $organizationId, $organizationId]
        );
    }

    public function roles(): array
    {
        return $this->all(
            'SELECT r.*, COUNT(DISTINCT rp.permission_id) AS permission_count, '
            . 'COUNT(DISTINCT ur.user_id) AS user_count FROM roles r '
            . 'LEFT JOIN role_permissions rp ON rp.role_id = r.id '
            . 'LEFT JOIN user_roles ur ON ur.role_id = r.id AND ur.organization_id = r.organization_id '
            . 'WHERE r.organization_id = ? GROUP BY r.id ORDER BY r.is_system DESC, r.name',
            [$this->organizationId()]
        );
    }

    public function permissions(): array
    {
        return $this->all('SELECT * FROM permissions ORDER BY module, name');
    }

    public function rolePermissionIds(int $roleId): array
    {
        return array_map('intval', array_column($this->all(
            'SELECT rp.permission_id FROM role_permissions rp INNER JOIN roles r ON r.id = rp.role_id '
            . 'WHERE rp.role_id = ? AND r.organization_id = ?',
            [$roleId, $this->organizationId()]
        ), 'permission_id'));
    }

    public function createRole(string $name, string $slug, string $description): int
    {
        $this->execute(
            'INSERT INTO roles (organization_id, name, slug, description) VALUES (?, ?, ?, ?)',
            [$this->organizationId(), $name, $slug, $description]
        );
        return $this->lastInsertId();
    }

    public function updateRole(int $roleId, string $name, string $description, bool $active): void
    {
        $this->execute(
            'UPDATE roles SET name = ?, description = ?, is_active = ?, updated_at = NOW() '
            . 'WHERE id = ? AND organization_id = ?',
            [$name, $description, (int) $active, $roleId, $this->organizationId()]
        );
    }

    public function syncPermissions(int $roleId, array $permissionIds): void
    {
        $this->transaction(function () use ($roleId, $permissionIds): void {
            $role = $this->first('SELECT id FROM roles WHERE id = ? AND organization_id = ?', [
                $roleId, $this->organizationId(),
            ]);
            if (!$role) {
                throw new \RuntimeException('Role not found.');
            }
            $this->execute('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);
            foreach (array_unique(array_map('intval', $permissionIds)) as $permissionId) {
                if ($permissionId > 0) {
                    $this->execute(
                        'INSERT INTO role_permissions (role_id, permission_id) '
                        . 'SELECT ?, id FROM permissions WHERE id = ?',
                        [$roleId, $permissionId]
                    );
                }
            }
        });
    }

    public function assignUserRole(int $userId, int $roleId): void
    {
        $organizationId = $this->organizationId();
        $this->transaction(function () use ($organizationId, $userId, $roleId): void {
            $valid = $this->scalar(
                'SELECT COUNT(*) FROM users u INNER JOIN roles r ON r.organization_id = u.organization_id '
                . 'WHERE u.id = ? AND r.id = ? AND u.organization_id = ?',
                [$userId, $roleId, $organizationId]
            );
            if (!(int) $valid) {
                throw new \RuntimeException('The user and role must belong to this workspace.');
            }
            $this->execute('DELETE FROM user_roles WHERE organization_id = ? AND user_id = ?', [$organizationId, $userId]);
            $this->execute(
                'INSERT INTO user_roles (organization_id, user_id, role_id) VALUES (?, ?, ?)',
                [$organizationId, $userId, $roleId]
            );
        });
    }
}
