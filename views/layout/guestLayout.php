<?php $e = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8'); $appName = $_ENV['APP_NAME'] ?? 'AssetFlow'; ?>
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="csrf-token" content="<?= $e($_SESSION['csrf_token'] ?? '') ?>"><meta name="theme-color" content="#0f766e">
<title><?= $e($appName) ?> · <?= $e($title ?? '') ?></title><link rel="stylesheet" href="/src/css/output.css"><link rel="stylesheet" href="/src/css/style.css">
<script src="/src/js/http.js"></script><script src="/src/js/script.js" defer></script><script src="https://cdn.jsdelivr.net/npm/sweetalert2@11.22.4/dist/sweetalert2.all.min.js" defer></script></head>
<body class="guest-body"><div class="guest-shell"><header class="guest-header"><a class="app-brand dark" href="/"><span class="brand-mark">AF</span><span><strong><?= $e($appName) ?></strong><small>IT asset management</small></span></a>
<a class="secondary-button" href="<?= isset($_SESSION['user_id']) ? '/dashboard' : '/login' ?>"><?= isset($_SESSION['user_id']) ? 'Dashboard' : 'Administrator sign in' ?></a></header>
<main class="guest-main"><section class="guest-intro"><p class="eyebrow">Assets with accountability</p><h1>Everything your IT team needs, without the clutter.</h1><p>Track inventory, employee custody, computer builds, lifecycle status, and audit-ready reports from one secure workspace.</p>
<div class="feature-row"><span>Role-based access</span><span>Configurable workflows</span><span>PDF reporting</span></div></section>
<section class="guest-content"><?php require BASE_PATH . '/views/components/alert.php'; ?><?= $content ?></section></main>
<footer class="guest-footer">&copy; <?= date('Y') ?> <?= $e($appName) ?> · Secure IT inventory</footer></div></body></html>
