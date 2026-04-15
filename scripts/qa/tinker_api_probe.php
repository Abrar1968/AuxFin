<?php

use Illuminate\Http\Request;

$loginRequest = Request::create(
    '/api/auth/login',
    'POST',
    [],
    [],
    [],
    ['CONTENT_TYPE' => 'application/json', 'HTTP_ACCEPT' => 'application/json'],
    json_encode([
        'email' => 'admin@finerp.local',
        'passkey' => 'Admin#2026',
    ], JSON_THROW_ON_ERROR)
);

$loginResponse = app()->handle($loginRequest);
$loginPayload = json_decode($loginResponse->getContent(), true);
$token = $loginPayload['token'] ?? null;

echo 'login_status=' . $loginResponse->getStatusCode() . PHP_EOL;

$generalRequest = Request::create(
    '/api/admin/settings/general',
    'GET',
    [],
    [],
    [],
    ['HTTP_ACCEPT' => 'application/json', 'HTTP_AUTHORIZATION' => 'Bearer ' . $token]
);

$generalResponse = app()->handle($generalRequest);
$generalPayload = json_decode($generalResponse->getContent(), true);

echo 'general_status=' . $generalResponse->getStatusCode() . PHP_EOL;
echo 'company=' . ($generalPayload['general_settings']['company_name'] ?? 'n/a') . PHP_EOL;
