<?php

// Copy this file outside public_html as "it-inventory-secrets.php".
// It must never be committed or downloadable. Restrict it to mode 600 when possible.
return [
    'APP_NAME' => 'AssetFlow',
    'APP_ENV' => 'production',
    'APP_URL' => 'https://inventory.example.com',
    'DB_HOST' => 'localhost',
    'DB_PORT' => '3306',
    'DB_NAME' => 'database_name',
    'DB_USER' => 'database_user',
    'DB_PASS' => 'replace-with-a-long-random-password',
    'BREVO_API' => '',
    'BREVO_EMAIL' => '',
    'CRON_TOKEN' => '',
    'PAYMENT_PROVIDER_KEY' => '',
    'PAYMENT_WEBHOOK_SECRET' => '',
];
