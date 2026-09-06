<?php
$e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
$appName = $appSettings['app.name'] ?? ($_ENV['APP_NAME'] ?? 'AssetFlow');
$fallbackNavigation = [
    ['label' => 'Dashboard', 'route' => '/dashboard', 'permission' => 'dashboard.view'],
    ['label' => 'Employees', 'route' => '/employee', 'permission' => 'employees.view'],
    ['label' => 'Parts', 'route' => '/parts', 'permission' => 'parts.view'],
    ['label' => 'Accessories', 'route' => '/accessories', 'permission' => 'accessories.view'],
    ['label' => 'Builds', 'route' => '/build', 'permission' => 'build.manage'],
    ['label' => 'Computers', 'route' => '/computer', 'permission' => 'computers.view'],
    ['label' => 'Reports', 'route' => '/reports', 'permission' => 'reports.view'],
    ['label' => 'CMS settings', 'route' => '/settings', 'permission' => 'catalogs.manage'],
];
$menuItems = $navigation ?: $fallbackNavigation;
$can = static fn(string $permission): bool => in_array('*', $permissions, true) || in_array($permission, $permissions, true);
$currentPath = '/' . trim((string) parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
?>
<!doctype html><html lang="en"><head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <meta name="csrf-token" content="<?= $e($_SESSION['csrf_token'] ?? '') ?>">
    <meta name="theme-color" content="<?= $e($appSettings['app.primary_color'] ?? '#0f766e') ?>">
    <title><?= $e($appName) ?> · <?= $e($title ?? '') ?></title>
    <link rel="stylesheet" href="/src/css/output.css"><link rel="stylesheet" href="/src/css/style.css">
    <script src="/src/js/http.js"></script><script src="/src/js/script.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.all.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.9/dist/cdn.min.js" defer></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.9/dist/chart.umd.min.js" defer></script>
</head><body class="app-body" style="--brand:<?= $e($appSettings['app.primary_color'] ?? '#0f766e') ?>" x-data="{sidebar:false,userMenu:false}">
<div class="app-frame">
    <div class="sidebar-backdrop" x-show="sidebar" x-transition.opacity @click="sidebar=false"></div>
    <aside class="app-sidebar" :class="sidebar && 'open'">
        <a class="app-brand" href="/dashboard"><span class="brand-mark">AF</span><span><strong><?= $e($appName) ?></strong><small>Inventory workspace</small></span></a>
        <nav class="sidebar-nav" aria-label="Primary navigation">
            <p class="nav-section">Workspace</p>
            <?php foreach ($menuItems as $item): $permission = (string) ($item['permission'] ?? ''); if ($permission !== '' && !$can($permission)) continue; $route = (string) $item['route']; $active = $currentPath === $route || ($route !== '/dashboard' && str_starts_with($currentPath, $route . '/')); ?>
                <a class="sidebar-link <?= $active ? 'active' : '' ?>" href="<?= $e($route) ?>"><span class="nav-dot"></span><?= $e($item['label']) ?></a>
            <?php endforeach; ?>
        </nav>
        <div class="sidebar-foot"><span class="security-dot"></span><div><strong>Protected workspace</strong><small>Tenant isolation & RBAC enabled</small></div></div>
    </aside>
    <div class="app-content">
        <header class="app-topbar"><button class="icon-button mobile-menu" @click="sidebar=true" aria-label="Open menu">☰</button>
            <div class="topbar-context"><small><?= $e($appName) ?></small><strong><?= $e($title ?? 'Workspace') ?></strong></div>
            <div class="user-menu"><button class="user-trigger" @click="userMenu=!userMenu" @click.outside="userMenu=false"><span class="avatar"><?= $e(strtoupper(substr((string) ($_SESSION['name'] ?? 'U'), 0, 1))) ?></span><span><strong><?= $e($_SESSION['name'] ?? 'User') ?></strong><small><?= $e($_SESSION['type'] ?? 'Member') ?></small></span><span>⌄</span></button>
                <div class="user-dropdown" x-show="userMenu" x-transition><a href="/profile">Profile & security</a><?php if ($can('backup.manage')): ?><a href="/backup">Tenant backup</a><?php endif; ?><hr><a class="danger" href="/logout">Sign out</a></div>
            </div>
        </header>
        <main class="app-main"><?php require BASE_PATH . '/views/components/alert.php'; ?><?= $content ?></main>
        <footer class="app-footer"><span>&copy; <?= date('Y') ?> <?= $e($appName) ?></span><span>SaaS-ready IT asset management</span></footer>
    </div>
</div></body></html>
