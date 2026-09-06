<?php

namespace System\Services;

use Brevo\Client\Api\TransactionalEmailsApi;
use Brevo\Client\Configuration;
use Brevo\Client\Model\SendSmtpEmail;
use GuzzleHttp\Client;

class EmailService
{
    private TransactionalEmailsApi $client;

    public function __construct()
    {
        $configuration = Configuration::getDefaultConfiguration();
        $configuration->setApiKey('api-key', $_ENV['BREVO_API'] ?? '');
        $this->client = new TransactionalEmailsApi(new Client(), $configuration);
    }

    public function send(string $email, string $name, string $subject, string $html): void
    {
        $message = new SendSmtpEmail([
            'subject' => $subject,
            'sender' => [
                'name' => $_ENV['APP_NAME'] ?? 'IT Inventory',
                'email' => $_ENV['BREVO_EMAIL'] ?? '',
            ],
            'to' => [['name' => $name, 'email' => $email]],
            'htmlContent' => $html,
        ]);
        $this->client->sendTransacEmail($message);
    }
}
