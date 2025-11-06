<?php
require __DIR__ . '/vendor/autoload.php'; // ha Composerrel telepítetted a Dompdf-et
require __DIR__ . '/src/pdf_helpers.php'; // ahol a függvény található

use Dompdf\Dompdf;
use Dompdf\Options;

// Tesztadatok
$invoiceData = [
    'invoiceNumber' => 'TESZT-2025-001',
    'issueDate' => '2025-11-06',
    'deliveryDate' => '2025-11-05',
    'paymentMethod' => 'Átutalás',
    'paymentDueDate' => '2025-11-13',
    'currency' => 'HUF',
    'seller' => [
        'name' => 'Minta Kft.',
        'taxNumber' => '12345678-1-42',
        'address' => [
            'postalCode' => '1111',
            'city' => 'Budapest',
            'street' => 'Teszt utca 10.',
            'countryCode' => 'HU'
        ],
    ],
    'buyer' => [
        'name' => 'Teszt Vevő Bt.',
        'taxNumber' => '87654321-2-13',
        'email' => 'vevo@example.com',
        'address' => [
            'postalCode' => '4024',
            'city' => 'Debrecen',
            'street' => 'Minta köz 3.',
            'countryCode' => 'HU'
        ],
    ],
    'items' => [
        [
            'desc' => 'Webfejlesztési szolgáltatás',
            'qty' => 10,
            'unit' => 'óra',
            'net' => 10000,
            'vatRate' => 27,
        ],
        [
            'desc' => 'Karbantartási díj',
            'qty' => 1,
            'unit' => 'hó',
            'net' => 50000,
            'vatRate' => 27,
        ],
    ],
];

// Nettó, ÁFA, bruttó összesítések
$totalsNet = array_sum(array_map(fn($i) => $i['net'] * $i['qty'], $invoiceData['items']));
$totalsVat = 0;
foreach ($invoiceData['items'] as $it) {
    $totalsVat += $it['net'] * $it['qty'] * ($it['vatRate'] / 100);
}
$invoiceData['totals'] = [
    'net' => $totalsNet,
    'vat' => $totalsVat,
    'gross' => $totalsNet + $totalsVat,
    'vatRateLabel' => '27%',
];

// Kimeneti fájl
$outputPath = __DIR__ . '/teszt_szamla.pdf';

// Függvény meghívása
$result = generateInvoicePdf($invoiceData, $outputPath);

// Eredmény kiírása
if (file_exists($result)) {
    echo "✅ Számla PDF sikeresen generálva: {$result}\n";
} else {
    echo "❌ Hiba történt a PDF generálás során.\n";
}
