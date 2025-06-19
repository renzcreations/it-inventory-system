<?php
if (!file_exists(BASE_PATH . '/.env')) {
    copy(BASE_PATH . '/.env.example', BASE_PATH . '/.env');
}

$env = parse_ini_file(BASE_PATH . '/.env');
foreach ($env as $key => $value) {
    $_ENV[$key] = $value;
}