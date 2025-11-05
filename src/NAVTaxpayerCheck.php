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
]);

$reporter = new Reporter($config);

try {
    // 🔍 Adószám megadása – bármely magyar vállalkozásé lehet
    $taxNumberToCheck = '12345678'; // teszt adószám vagy saját
    echo "Adószám lekérdezés: {$taxNumberToCheck}\n";

    $response = $reporter->queryTaxpayer($taxNumberToCheck);
    $info = $response['taxpayerData'];

    $msg = date('c') . " - {$taxNumberToCheck} -> {$info['taxpayerName']}\n";
    file_put_contents(__DIR__ . '/../logs/nav_taxpayer.log', $msg, FILE_APPEND);

    echo "✅ NAV válasz rendben:\n";
    echo "  Cégnév: " . $info['taxpayerName'] . "\n";
    echo "  Cím: " . $info['taxpayerAddress']['city'] . ", " .
        $info['taxpayerAddress']['streetName'] . " " .
        $info['taxpayerAddress']['publicPlaceCategory'] . " " .
        $info['taxpayerAddress']['number'] . "\n";

} catch (Exception $e) {
    $msg = date('c') . " - {$taxNumberToCheck} -> ERROR: {$e->getMessage()}\n";
    file_put_contents(__DIR__ . '/../logs/nav_taxpayer.log', $msg, FILE_APPEND);
    echo "❌ Hiba az adószám lekérdezés során: " . $e->getMessage() . "\n";
}
