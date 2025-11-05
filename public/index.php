<?php
require __DIR__ . '/../vendor/autoload.php';

$requestUri = $_SERVER['REQUEST_URI'];
$requestMethod = $_SERVER['REQUEST_METHOD'];

if ($requestUri === '/webhooks/stripe' && $requestMethod === 'POST') {
    require __DIR__ . '/../src/WebhookStripe.php';
} else {
    http_response_code(404);
    echo 'Not Found';
}
