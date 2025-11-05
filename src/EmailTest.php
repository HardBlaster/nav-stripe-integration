<?php
require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/email_helpers.php';

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// próba adatok (4.1 alapján)
$invoiceData = [
  'invoiceNumber' => 'TEST-001',
  'buyer' => ['name' => 'Teszt Elek', 'email' => 'teszt@domain.hu'],
  'seller' => ['name' => $_ENV['MAIL_FROM_NAME']],
];
$pdfPath = __DIR__ . '/../logs/invoice.pdf';

sendInvoiceEmail($invoiceData, $pdfPath);
