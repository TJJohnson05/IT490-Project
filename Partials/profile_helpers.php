<?php
// Shared output escaping and RabbitMQ transport for profile pages.
function profileEscape($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function profileRequest(array $request): array
{
    require_once __DIR__ . '/../rabbitMQLib.inc';
    $client = new rabbitMQClient(__DIR__ . '/../testRabbitMQDatabase.ini', 'databaseServer');
    $response = $client->send_request($request);
    if (!is_array($response) || !isset($response['success'])) {
        throw new RuntimeException('Profile service is not installed or returned an invalid response.');
    }
    return $response;
}
function profileInput(string $key): string
{
    return isset($_POST[$key]) && is_string($_POST[$key]) ? $_POST[$key] : '';
}