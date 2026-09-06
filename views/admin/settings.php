<?php
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$byGroup = [];
foreach ($values as $value) {
    $byGroup[(int) $value['group_id']][] = $value;
}
?>
<section class="page-shell" x-data="{tab: new URLSearchParams(location.search).get('tab') || 'branding'}">
    <div class="page-heading">
        <div><p class="eyebrow">Administration</p><h1>CMS & access control</h1>
            <p>Manage branding, dropdown values, lifecycle statuses, roles, and permissions without editing code.</p></div>
    </div>

    <div class="settings-tabs" role="tablist">
        <?php foreach (['branding' => 'Workspace', 'catalogs' => 'Catalogs', 'roles' => 'Roles & permissions', 'users' => 'User roles', 'audit' => 'Audit log'] as $key => $label): ?>
            <button type="button" @click="tab='<?= $key ?>'" :class="tab==='<?= $key ?>' && 'active'" class="tab-button"><?= $label ?></button>
        <?php endforeach; ?>
    </div>

    <div x-show="tab==='branding'" class="admin-grid">
        <form method="post" action="/settings/general" class="panel form-grid">
            <div class="panel-heading"><div><h2>Workspace identity</h2><p>Shown throughout the application and generated reports.</p></div></div>
            <label>Application name<input name="app.name" value="<?= $e($settings['app.name'] ?? 'IT Inventory') ?>" required></label>
            <label>Tagline<input name="app.tagline" value="<?= $e($settings['app.tagline'] ?? '') ?>"></label>
            <label>Primary color<input name="app.primary_color" type="color" value="<?= $e($settings['app.primary_color'] ?? '#0f766e') ?>"></label>
            <label>Timezone<input name="app.timezone" value="<?= $e($settings['app.timezone'] ?? 'Asia/Manila') ?>"></label>
            <label>Date format<input name="app.date_format" value="<?= $e($settings['app.date_format'] ?? 'M j, Y') ?>"></label>
            <label>Report footer<input name="reports.footer" value="<?= $e($settings['reports.footer'] ?? '') ?>"></label>
            <button class="primary-button" type="submit">Save workspace</button>
        </form>
    </div>

    <div x-show="tab==='catalogs'" class="stack-lg">
        <details class="panel"><summary>Add a catalog group</summary>
            <form method="post" action="/settings/catalog-group" class="inline-form">
                <label>Name<input name="name" required></label><label>Stable code<input name="code" pattern="[a-z0-9_]+" required></label>
                <label>Entity<input name="entity_type" placeholder="employee"></label><label>Sort<input type="number" name="sort_order" value="100"></label>
                <label class="wide">Description<input name="description"></label><button class="primary-button">Create group</button>
            </form>
        </details>
        <?php foreach ($groups as $group): ?>
            <article class="panel">
                <div class="panel-heading"><div><p class="eyebrow"><?= $e($group['entity_type']) ?></p><h2><?= $e($group['name']) ?></h2><p><?= $e($group['description']) ?></p></div><span class="count-badge"><?= (int) $group['value_count'] ?> values</span></div>
                <div class="catalog-list">
                    <?php foreach ($byGroup[(int) $group['id']] ?? [] as $value): ?>
                        <form method="post" action="/settings/catalog-value" class="catalog-row">
                            <input type="hidden" name="id" value="<?= (int) $value['id'] ?>">
                            <input name="name" aria-label="Name" value="<?= $e($value['name']) ?>" required>
                            <input name="description" aria-label="Description" value="<?= $e($value['description']) ?>" placeholder="Description">
                            <input name="color" aria-label="Color" value="<?= $e($value['color']) ?>" placeholder="Color">
                            <input type="number" name="sort_order" aria-label="Sort order" value="<?= (int) $value['sort_order'] ?>">
                            <label class="check"><input type="checkbox" name="is_active" <?= $value['is_active'] ? 'checked' : '' ?>> Active</label>
                            <button class="secondary-button">Save</button>
                        </form>
                    <?php endforeach; ?>
                    <form method="post" action="/settings/catalog-value" class="catalog-row add-row">
                        <input type="hidden" name="group_id" value="<?= (int) $group['id'] ?>">
                        <input name="name" placeholder="New value name" required><input name="code" placeholder="stable-code" required>
                        <input name="description" placeholder="Description"><input name="color" value="slate"><input type="number" name="sort_order" value="100">
                        <input type="hidden" name="is_active" value="1"><button class="primary-button">Add value</button>
                    </form>
                </div>
            </article>
        <?php endforeach; ?>
    </div>

    <div x-show="tab==='roles'" class="stack-lg">
        <details class="panel"><summary>Create a custom role</summary><form method="post" action="/settings/role" class="inline-form">
            <label>Name<input name="name" required></label><label class="wide">Description<input name="description"></label><button class="primary-button">Create role</button>
        </form></details>
        <?php foreach ($roles as $role): ?>
            <article class="panel role-card"><div class="panel-heading"><div><h2><?= $e($role['name']) ?></h2><p><?= $e($role['description']) ?></p></div><span class="count-badge"><?= (int) $role['user_count'] ?> users</span></div>
                <form method="post" action="/settings/role/permissions">
                    <input type="hidden" name="role_id" value="<?= (int) $role['id'] ?>"><div class="permission-grid">
                    <?php foreach ($permissionList as $permission): ?>
                        <label class="permission-option"><input type="checkbox" name="permissions[]" value="<?= (int) $permission['id'] ?>" <?= in_array((int) $permission['id'], $rolePermissions[(int) $role['id']] ?? [], true) ? 'checked' : '' ?>>
                            <span><strong><?= $e($permission['name']) ?></strong><small><?= $e($permission['module']) ?></small></span></label>
                    <?php endforeach; ?></div><button class="primary-button">Save permissions</button>
                </form>
            </article>
        <?php endforeach; ?>
    </div>

    <div x-show="tab==='users'" class="panel table-wrap"><table class="data-table"><thead><tr><th>User</th><th>Email</th><th>Current role</th><th>Assign role</th></tr></thead><tbody>
        <?php foreach ($users as $user): ?><tr><td><strong><?= $e($user['name']) ?></strong><small>@<?= $e($user['username']) ?></small></td><td><?= $e($user['email']) ?></td><td><?= $e($user['role_name'] ?? 'Unassigned') ?></td><td>
            <form method="post" action="/settings/user-role" class="row-action"><input type="hidden" name="user_id" value="<?= (int) $user['id'] ?>"><select name="role_id" required>
                <?php foreach ($roles as $role): ?><option value="<?= (int) $role['id'] ?>" <?= (int) ($user['role_id'] ?? 0) === (int) $role['id'] ? 'selected' : '' ?>><?= $e($role['name']) ?></option><?php endforeach; ?>
            </select><button class="secondary-button">Assign</button></form></td></tr><?php endforeach; ?>
    </tbody></table></div>

    <div x-show="tab==='audit'" class="panel table-wrap"><table class="data-table"><thead><tr><th>Time</th><th>User</th><th>Action</th><th>Entity</th><th>IP</th></tr></thead><tbody>
        <?php foreach ($auditLogs as $log): ?><tr><td><?= $e($log['created_at']) ?></td><td><?= $e($log['user_name'] ?? 'System') ?></td><td><code><?= $e($log['action']) ?></code></td><td><?= $e(trim(($log['entity_type'] ?? '') . ' ' . ($log['entity_id'] ?? ''))) ?></td><td><?= $e($log['ip_address']) ?></td></tr><?php endforeach; ?>
    </tbody></table></div>
</section>
