<?php
use NavOnlineInvoice\Config;

function createNavConfig() {
    $apiUrl = ($_ENV['NAV_ENV'] === 'prod')
        ? Config::API_URL_PROD
        : Config::API_URL_TEST;

    return new Config([
        'apiUrl'      => $apiUrl,
        'login'       => $_ENV['NAV_LOGIN'],
        'password'    => $_ENV['NAV_PASSWORD'],
        'taxNumber'   => $_ENV['NAV_TAX_NUMBER'],
        'signKey'     => $_ENV['NAV_SIGN_KEY'],
        'exchangeKey' => $_ENV['NAV_EXCHANGE_KEY'],
        'softwareId'  => $_ENV['NAV_SOFTWARE_ID'],
        'softwareName' => 'Stripe Integration',
        'softwareOperation' => 'ONLINE_SERVICE',
        'softwareMainVersion' => '1.0'
    ]);
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
