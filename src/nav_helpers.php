<?php
use NavOnlineInvoice\Config;

function createNavConfig() {
    $dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
    $dotenv->load();
    print_r($_ENV);
    $userData = array(
        "login" => $_ENV['NAV_TECHNICAL_USER'],
        "password" => $_ENV['NAV_TECHNICAL_PASSWORD'],
        "passwordHash" => $_ENV['NAV_TECHNICAL_PASSWORD_HASH'],
        "taxNumber" => $_ENV['NAV_TAX_NUMBER'],
        "signKey" => $_ENV['NAV_SIGNATURE_KEY'],
        "exchangeKey" => $_ENV['NAV_EXCHANGE_KEY'],
    );

    $softwareData = array(
        "softwareId" => $_ENV['NAV_SOFTWARE_ID'],
        "softwareName" => $_ENV['NAV_SOFTWARE_NAME'],
        "softwareOperation" => "ONLINE_SERVICE",
        "softwareMainVersion" => $_ENV['NAV_SOFTWARE_VERSION'],
        "softwareDevName" => $_ENV['NAV_SOFTWARE_DEV_NAME'],
        "softwareDevContact" => $_ENV['NAV_SOFTWARE_DEV_CONTACT'],
        "softwareDevCountryCode" => "HU",
        "softwareDevTaxNumber" => $_ENV['NAV_SOFTWARE_DEV_TAX_NUMBER'],
    );

    $apiUrl = $_ENV['NAV_BASE_URL'];

    return new Config($apiUrl, $userData, $softwareData);
}

function checkTaxNumber($taxNumber) {
    $config = createNavConfig();
    $reporter = new \NavOnlineInvoice\Reporter($config);

    try {
        $response = $reporter->queryTaxpayer($taxNumber);
        return $response['taxpayerData'];
    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../logs/nav_taxpayer.log',
            date('c') . " - {$taxNumber} -> ERROR: {$e->getMessage()}\n",
            FILE_APPEND
        );
        return null;
    }
}
