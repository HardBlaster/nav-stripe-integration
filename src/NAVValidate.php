<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/nav_helpers.php';

use NavOnlineInvoice\Reporter;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$config = createNavConfig();
$reporter = new Reporter($config);

$xmlPath = __DIR__ . '/../logs/invoice_preview.xml';

if (!file_exists($xmlPath)) {
    echo "❌ Nem található a számla XML: {$xmlPath}\n";
    exit(1);
}

$invoiceXml = file_get_contents($xmlPath);

try {
    $errors = $reporter->getInvoiceValidationError($invoiceXml);

    if (empty($errors)) {
        echo "✅ NAV XSD validáció rendben – nincs hiba.\n";
        file_put_contents(__DIR__ . '/../logs/nav_validation.log',
            date('c') . " - Validation OK\n", FILE_APPEND
        );
    } else {
        echo "❌ NAV validációs hibák:\n";
        foreach ($errors as $error) {
            echo "  - " . $error . "\n";
            file_put_contents(__DIR__ . '/../logs/nav_validation.log',
                date('c') . " - ERROR: {$error}\n", FILE_APPEND
            );
        }
    }

} catch (Exception $e) {
    echo "❌ Validációs hiba futás közben: {$e->getMessage()}\n";
    file_put_contents(__DIR__ . '/../logs/nav_validation.log',
        date('c') . " - Exception: {$e->getMessage()}\n", FILE_APPEND
    );
}
