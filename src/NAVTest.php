<?php
require __DIR__ . '/../vendor/autoload.php';

use NavOnlineInvoice\Config;
use NavOnlineInvoice\Reporter;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$apiUrl = ($_ENV['NAV_ENV'] === 'prod')
    ? Config::API_URL_PROD
    : Config::API_URL_TEST;

$config = new Config([
    'apiUrl'      => $apiUrl,
    'login'       => $_ENV['NAV_LOGIN'],
    'password'    => $_ENV['NAV_PASSWORD'],
    'taxNumber'   => $_ENV['NAV_TAX_NUMBER'],
    'signKey'     => $_ENV['NAV_SIGN_KEY'],
    'exchangeKey' => $_ENV['NAV_EXCHANGE_KEY'],
    'softwareId'  => $_ENV['NAV_SOFTWARE_ID'],
    'softwareName' => 'Stripe Integration Test',
    'softwareOperation' => 'ONLINE_SERVICE',
    'softwareMainVersion' => '1.0'
]);

$reporter = new Reporter($config);

try {
    $response = $reporter->tokenExchange();
    echo "✅ NAV kapcsolat rendben!\n";
    echo "Exchange token: " . $response['encodedExchangeToken'] . "\n";
} catch (Exception $e) {
    $msg = date('c') . " - NAV connection error: " . $e->getMessage() . "\n";
    file_put_contents(__DIR__ . '/../logs/nav_test.log', $msg, FILE_APPEND);
    echo "❌ Hiba a NAV kapcsolatban: " . $e->getMessage() . "\n";
}
