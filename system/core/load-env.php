<?php

/**
 * Load secrets without requiring a web-accessible .env file.
 *
 * Production priority:
 * 1. IT_INVENTORY_SECRETS server variable pointing outside public_html.
 * 2. ../it-inventory-secrets.php (one directory above the application).
 * 3. config/secrets.php (supported, but the config directory must stay denied).
 * 4. config/.secrets (legacy INI values moved from the old .env during upgrade).
 */
$configuredPath = getenv('IT_INVENTORY_SECRETS') ?: '';
$candidates = array_filter([
    $configuredPath,
    dirname(BASE_PATH) . '/it-inventory-secrets.php',
    BASE_PATH . '/config/secrets.php',
]);

$loaded = [];
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $values = require $candidate;
        if (!is_array($values)) {
            throw new RuntimeException('The secrets file must return an array.');
        }
        $loaded = $values;
        break;
    }
}

$legacySecrets = BASE_PATH . '/config/.secrets';
if ($loaded === [] && is_file($legacySecrets)) {
    $values = parse_ini_file($legacySecrets, false, INI_SCANNER_RAW);
    $loaded = is_array($values) ? $values : [];
}

foreach ($loaded as $key => $value) {
    if (is_string($key)) {
        $_ENV[$key] = (string) $value;
    }
}

foreach (['APP_NAME', 'APP_ENV', 'APP_URL', 'DB_HOST', 'DB_PORT', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $key) {
    $serverValue = getenv($key);
    if ($serverValue !== false) {
        $_ENV[$key] = $serverValue;
    }
}

$_ENV['APP_NAME'] ??= 'AssetFlow';
$_ENV['APP_ENV'] ??= 'production';
$_ENV['APP_URL'] ??= '';
$_ENV['DB_PORT'] ??= '3306';

foreach (['DB_HOST', 'DB_NAME', 'DB_USER', 'DB_PASS'] as $required) {
    if (!array_key_exists($required, $_ENV)) {
        throw new RuntimeException("Missing required secret: {$required}");
    }
}
