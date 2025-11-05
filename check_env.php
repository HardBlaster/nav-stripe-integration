<?php
require 'vendor/autoload.php';

// dotenv betöltése
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__);
$dotenv->load();

print_r($_ENV);

echo "Stripe key: " . $_ENV['STRIPE_SECRET_KEY'] . PHP_EOL;
echo "NAV login: " . $_ENV['NAV_LOGIN'] . PHP_EOL;
echo "Mailer DSN: " . $_ENV['MAILER_DSN'] . PHP_EOL;
