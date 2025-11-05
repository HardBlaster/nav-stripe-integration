<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/nav_helpers.php';

use NavOnlineInvoice\Reporter;
use NavOnlineInvoice\InvoiceOperation;
use NavOnlineInvoice\InvoiceOperations;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = createNavConfig();
$reporter = new Reporter($config);

// 1️⃣ Betöltjük a számla XML-t
$xmlPath = __DIR__ . '/../logs/invoice_preview.xml';
if (!file_exists($xmlPath)) {
    echo "❌ Nem található az XML: {$xmlPath}\n";
    exit(1);
}

$invoiceXml = file_get_contents($xmlPath);

// 2️⃣ InvoiceOperations objektum összeállítása
$operations = new InvoiceOperations();
$operations->add(new InvoiceOperation($invoiceXml, "CREATE"));

// 3️⃣ Beküldés
try {
    $result = $reporter->manageInvoice($operations);
    $transactionId = $result['transactionId'] ?? null;

    // Logolás
    file_put_contents(__DIR__ . '/../logs/nav_send.log',
        date('c') . " - Sent invoice, transactionId: {$transactionId}\n",
        FILE_APPEND
    );

    echo "✅ Számla beküldve a NAV-hoz (teszt környezet)\n";
    echo "Transaction ID: {$transactionId}\n";

    // 4️⃣ Debug információ mentése
    $debug = $reporter->getLastRequestData();
    file_put_contents(__DIR__ . '/../logs/nav_request_debug.json', json_encode($debug, JSON_PRETTY_PRINT));

} catch (Exception $e) {
    echo "❌ Hiba a beküldés során: {$e->getMessage()}\n";
    file_put_contents(__DIR__ . '/../logs/nav_send.log',
        date('c') . " - Error: {$e->getMessage()}\n",
        FILE_APPEND
    );
    exit(1);
}
