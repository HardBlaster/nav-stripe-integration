<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/pdf_helpers.php';

$invoiceData = require __DIR__ . '/_fixture_invoice.php'; // vagy építsd össze kézzel
$target = __DIR__ . '/../logs/invoice.pdf';

$path = generateInvoicePdf($invoiceData, $target);
echo "✅ PDF generálva: {$path}\n";
