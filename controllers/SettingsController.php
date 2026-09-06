<?php

namespace Controllers;

use Models\AuditModel;
use Models\CatalogModel;
use Models\RbacModel;
use Models\UserModel;
use System\Core\Controller;
use Throwable;

class SettingsController extends Controller
{
    public function __construct(
        private ?CatalogModel $catalogs = null,
        private ?RbacModel $rbac = null,
        private ?AuditModel $audit = null
    ) {
        $this->catalogs ??= new CatalogModel();
        $this->rbac ??= new RbacModel();
        $this->audit ??= new AuditModel();
    }

    public function index(): void
    {
        $this->view('admin/settings', [
            'title' => 'CMS & access control',
            'groups' => $this->catalogs->groups(),
            'values' => $this->catalogs->values(),
            'settings' => $this->catalogs->settings(),
            'roles' => $this->rbac->roles(),
            'permissionList' => $this->rbac->permissions(),
            'rolePermissions' => array_reduce($this->rbac->roles(), function (array $carry, array $role): array {
                $carry[(int) $role['id']] = $this->rbac->rolePermissionIds((int) $role['id']);
                return $carry;
            }, []),
            'users' => (new UserModel())->allUsers(),
            'auditLogs' => $this->audit->recent(),
        ]);
    }

    public function saveGeneral(): never
    {
        $this->catalogs->saveSettings($_POST);
        $this->audit->record('settings.updated', 'settings');
        $this->flash('success', 'Workspace settings updated.');
    }

    public function createGroup(): never
    {
        try {
            $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
            $code = strtolower(trim((string) ($_POST['code'] ?? '')));
            $code = preg_replace('/[^a-z0-9_]+/', '_', $code);
            if ($name === '' || $code === '') {
                throw new \InvalidArgumentException('A group name and code are required.');
            }
            $id = $this->catalogs->createGroup([
                'name' => $name,
                'code' => $code,
                'description' => $this->sanitize_input($_POST['description'] ?? ''),
                'entity_type' => $this->sanitize_input($_POST['entity_type'] ?? ''),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
            ]);
            $this->audit->record('catalog.group.created', 'catalog_group', (string) $id, ['name' => $name]);
            $this->flash('success', 'Catalog group created.');
        } catch (Throwable $exception) {
            $this->flash('error', $exception->getMessage());
        }
    }

    public function saveValue(): never
    {
        try {
            $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
            $code = strtolower(trim((string) ($_POST['code'] ?? '')));
            $code = preg_replace('/[^a-z0-9_-]+/', '-', $code);
            if ($name === '' || (empty($_POST['id']) && ($code === '' || empty($_POST['group_id'])))) {
                throw new \InvalidArgumentException('Name, code, and group are required.');
            }
            $id = $this->catalogs->saveValue([
                'id' => (int) ($_POST['id'] ?? 0),
                'group_id' => (int) ($_POST['group_id'] ?? 0),
                'name' => $name,
                'code' => $code,
                'description' => $this->sanitize_input($_POST['description'] ?? ''),
                'color' => preg_replace('/[^a-z0-9#-]/i', '', (string) ($_POST['color'] ?? 'slate')),
                'sort_order' => (int) ($_POST['sort_order'] ?? 0),
                'is_active' => isset($_POST['is_active']) ? 1 : 0,
            ]);
            $this->audit->record('catalog.value.saved', 'catalog_value', (string) $id, ['name' => $name]);
            $this->flash('success', 'Catalog value saved.');
        } catch (Throwable $exception) {
            $this->flash('error', $exception->getMessage());
        }
    }

    public function removeValue(): never
    {
        $id = (int) ($_POST['id'] ?? 0);
        $this->catalogs->deactivateValue($id);
        $this->audit->record('catalog.value.deactivated', 'catalog_value', (string) $id);
        $this->flash('success', 'Catalog value deactivated. Existing records were preserved.');
    }

    public function saveRole(): never
    {
        try {
            $id = (int) ($_POST['id'] ?? 0);
            $name = $this->sanitize_input($_POST['name'] ?? '', 'ucwords');
            if ($name === '') {
                throw new \InvalidArgumentException('Role name is required.');
            }
            if ($id > 0) {
                $this->rbac->updateRole($id, $name, $this->sanitize_input($_POST['description'] ?? ''), isset($_POST['is_active']));
            } else {
                $slug = preg_replace('/[^a-z0-9-]+/', '-', strtolower($name));
                $id = $this->rbac->createRole($name, trim($slug, '-'), $this->sanitize_input($_POST['description'] ?? ''));
            }
            $this->audit->record('role.saved', 'role', (string) $id, ['name' => $name]);
            $this->flash('success', 'Role saved.');
        } catch (Throwable $exception) {
            $this->flash('error', $exception->getMessage());
        }
    }

    public function syncPermissions(): never
    {
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $this->rbac->syncPermissions($roleId, (array) ($_POST['permissions'] ?? []));
        $this->audit->record('role.permissions.synced', 'role', (string) $roleId);
        $this->flash('success', 'Role permissions updated.');
    }

    public function assignUserRole(): never
    {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $roleId = (int) ($_POST['role_id'] ?? 0);
        $this->rbac->assignUserRole($userId, $roleId);
        $this->audit->record('user.role.assigned', 'user', (string) $userId, ['role_id' => $roleId]);
        $this->flash('success', 'User role updated.');
    }

    private function flash(string $type, string $message): never
    {
        $_SESSION[$type] = $message;
        $this->redirect('/settings');
    }
}
