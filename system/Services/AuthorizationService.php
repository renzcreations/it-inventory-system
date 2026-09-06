<?php

namespace System\Services;

use Models\RbacModel;
use Throwable;

class AuthorizationService
{
    private static ?array $permissionCache = null;

    public function __construct(private ?RbacModel $rbac = null)
    {
        $this->rbac ??= new RbacModel();
    }

    public function can(string $permission): bool
    {
        if (empty($_SESSION['user_id'])) {
            return false;
        }

        if (self::$permissionCache === null) {
            try {
                $rows = $this->rbac->permissionsForUser(
                    (int) $_SESSION['user_id'],
                    max(1, (int) ($_SESSION['organization_id'] ?? 1))
                );
                self::$permissionCache = array_fill_keys(array_column($rows, 'slug'), true);
            } catch (Throwable $exception) {
                error_log('RBAC lookup failed: ' . $exception->getMessage());
                // Authorization errors fail closed. Run the additive migration before deploying this release.
                self::$permissionCache = [];
            }
        }

        return isset(self::$permissionCache['*']) || isset(self::$permissionCache[$permission]);
    }

    public function all(): array
    {
        $this->can('__warm_cache__');
        return array_keys(self::$permissionCache ?? []);
    }
}
