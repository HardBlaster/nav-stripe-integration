<?php
require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/InvoiceBuilder.php';
require_once __DIR__ . '/nav_helpers.php';
require_once __DIR__ . '/pdf_helpers.php';
require_once __DIR__ . '/email_helpers.php';

use Stripe\Webhook;
use Stripe\Stripe;
use Stripe\Invoice;
use Stripe\Customer;
use Stripe\PaymentIntent;

function handleInvoicePaymentSucceeded($invoiceObject) {
    try {
        // 1️⃣ Stripe inicializálás
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        // 2️⃣ Adatok lekérése
        $invoiceId = $invoiceObject->id;
        $invoice = Invoice::retrieve($invoiceId);
        $customer = Customer::retrieve($invoice->customer);
        $paymentIntent = PaymentIntent::retrieve($invoice->payment_intent);

        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - Stripe data retrieved for invoice: {$invoiceId}\n",
            FILE_APPEND
        );

    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - Stripe API error: {$e->getMessage()}\n",
            FILE_APPEND
        );
        return; // ne folytassa a feldolgozást
    }

    // 3️⃣ Vevőadatok összeállítása
    $isBusiness = !empty($customer->tax_id_data);
    $buyerData = [
        'name' => $customer->name ?? $customer->email,
        'email' => $customer->email,
        'taxNumber' => $customer->tax_id_data[0]['value'] ?? null,
        'type' => $isBusiness ? 'company' : 'private',
        'address' => [
            'countryCode' => $customer->address->country ?? 'HU',
            'postalCode' => $customer->address->postal_code ?? '',
            'city' => $customer->address->city ?? '',
            'street' => trim(($customer->address->line1 ?? '') . ' ' . ($customer->address->line2 ?? ''))
        ]
    ];

    // 4️⃣ Tételek konvertálása
    $items = [];
    foreach ($invoice->lines->data as $line) {
        $items[] = [
            'description' => $line->description ?? 'N/A',
            'quantity' => $line->quantity ?? 1,
            'amountNet' => ($line->amount / 100),
        ];
    }

    // 5️⃣ Stripe adatok strukturálása az InvoiceBuilder-hez
    $stripeData = [
        'invoiceNumber' => $invoice->number ?? ('INV-' . time()),
        'currency' => strtoupper($invoice->currency),
        'customer' => $buyerData,
        'items' => $items,
    ];

    // 6️⃣ NAV XML generálása (AAM)
    try {
        $xml = InvoiceBuilder::buildInvoiceXml($stripeData);
        $xmlPath = __DIR__ . '/../logs/invoice_' . $stripeData['invoiceNumber'] . '.xml';
        file_put_contents($xmlPath, $xml);

        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - Invoice XML generated: {$xmlPath}\n",
            FILE_APPEND
        );

    } catch (Exception $e) {
        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - XML generation error: {$e->getMessage()}\n",
            FILE_APPEND
        );
        return;
    }

    // 7️⃣ Alap információk logolása
    $total = $invoice->total / 100;
    file_put_contents(__DIR__ . '/../logs/webhook.log',
        "  Customer: {$buyerData['name']} <{$buyerData['email']}>\n" .
        "  Total: {$total} {$stripeData['currency']}\n" .
        "  Type: {$buyerData['type']}\n" .
        "  XML saved to: {$xmlPath}\n\n",
        FILE_APPEND
    );

    $invoiceData = [
        'invoiceNumber' => $stripeData['invoiceNumber'],
        'issueDate' => date('Y-m-d'),
        'deliveryDate' => date('Y-m-d'),
        'currency' => $stripeData['currency'],
        'paymentMethod' => $paymentIntent->payment_method_types[0] ?? 'card',
        'paymentDueDate' => date('Y-m-d'),
        'seller' => [
            'name' => $_ENV['SELLER_NAME'],
            'taxNumber' => $_ENV['SELLER_TAX_NUMBER'],
            'address' => [
                'postalCode' => $_ENV['SELLER_ADDRESS_POSTCODE'],
                'city' => $_ENV['SELLER_ADDRESS_CITY'],
                'street' => $_ENV['SELLER_ADDRESS_STREET'],
                'countryCode' => $_ENV['SELLER_COUNTRY_CODE']
            ]
        ],
        'buyer' => $buyerData,
        'items' => array_map(fn($i) => [
            'desc' => $i['description'],
            'qty' => $i['quantity'],
            'unit' => 'db',
            'net' => $i['amountNet'],
        ], $items),
        'totals' => [
            'net' => $total,
            'vat' => 0,
            'gross' => $total,
            'vatRateLabel' => 'AAM'
        ]
    ];

    // 8️⃣ A következő lépésben itt fog bekapcsolódni a NAV beküldés.
    try {
        $config = createNavConfig();
        $reporter = new \NavOnlineInvoice\Reporter($config);

        // --- XML validálás (opcionális, de ajánlott)
        $validationErrors = $reporter->getInvoiceValidationError($xml);
        if (!empty($validationErrors)) {
            file_put_contents(__DIR__ . '/../logs/webhook.log',
                date('c') . " - NAV XML validation errors: " . implode('; ', $validationErrors) . "\n",
                FILE_APPEND
            );
            // Nem küldjük be a hibás XML-t
            return;
        }

        // --- Beküldés (CREATE)
        $operations = new \NavOnlineInvoice\InvoiceOperations();
        $operations->add(new \NavOnlineInvoice\InvoiceOperation($xml, "CREATE"));

        $result = $reporter->manageInvoice($operations);
        $transactionId = $result['transactionId'] ?? null;

        // --- Logolás
        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - NAV manageInvoice CREATE sent. Transaction ID: {$transactionId}\n",
            FILE_APPEND
        );

        // --- Debug mentés (request/response)
        $debug = $reporter->getLastRequestData();
        file_put_contents(__DIR__ . '/../logs/nav_request_' . $stripeData['invoiceNumber'] . '.json',
            json_encode($debug, JSON_PRETTY_PRINT)
        );

        // --- Transaction ID elmentése külön
        if ($transactionId) {
            file_put_contents(__DIR__ . '/../logs/last_transaction_id.txt', $transactionId . PHP_EOL, FILE_APPEND);
        }

    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - NAV send error: {$e->getMessage()}\n", FILE_APPEND
        );
        // Hibánál nem áll le a folyamat (megy tovább PDF/E-mail)
    }

    try {
        $pdfPath = __DIR__ . '/../logs/invoice_' . $stripeData['invoiceNumber'] . '.pdf';
        generateInvoicePdf($invoiceData, $pdfPath);
        sendInvoiceEmail($invoiceData, $pdfPath);

        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - Email sent with attached PDF: {$pdfPath}\n", FILE_APPEND);

    } catch (Throwable $e) {
        file_put_contents(__DIR__ . '/../logs/webhook.log',
            date('c') . " - PDF/Email error: {$e->getMessage()}\n", FILE_APPEND);
    }

}

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

$endpoint_secret = $_ENV['STRIPE_WEBHOOK_SECRET'] ?? null;
$payload = @file_get_contents('php://input');
$sig_header = $_SERVER['HTTP_STRIPE_SIGNATURE'] ?? null;

file_put_contents(__DIR__ . '/../logs/webhook.log', str_repeat('-', 40) . "\n", FILE_APPEND);
file_put_contents(__DIR__ . '/../logs/webhook.log', date('c') . " - New request received\n", FILE_APPEND);

if (!$sig_header) {
    http_response_code(400);
    echo 'Missing Stripe-Signature header';
    exit;
}

try {
    $event = Webhook::constructEvent($payload, $sig_header, $endpoint_secret);
    file_put_contents(__DIR__ . '/../logs/webhook.log',
        date('c') . " - Verified event: {$event->type}\n",
        FILE_APPEND
    );
} catch (\UnexpectedValueException $e) {
    file_put_contents(__DIR__ . '/../logs/webhook.log', date('c') . " - Invalid payload\n", FILE_APPEND);
    http_response_code(400);
    echo 'Invalid payload';
    exit;
} catch (\Stripe\Exception\SignatureVerificationException $e) {
    file_put_contents(__DIR__ . '/../logs/webhook.log', date('c') . " - Invalid signature\n", FILE_APPEND);
    http_response_code(400);
    echo 'Invalid signature';
    exit;
} catch (Exception $e) {
    file_put_contents(__DIR__ . '/../logs/webhook.log', date('c') . " - Generic error: {$e->getMessage()}\n", FILE_APPEND);
    http_response_code(400);
    echo 'Invalid webhook';
    exit;
}

// --- Esemény felismerése ---
$eventType = $event->type;
file_put_contents(__DIR__ . '/../logs/webhook.log',
    date('c') . " - Event received: {$eventType}\n",
    FILE_APPEND
);

if ($eventType === 'invoice.payment_succeeded') {
    handleInvoicePaymentSucceeded($event->data->object);
} else {
    file_put_contents(__DIR__ . '/../logs/webhook.log',
        date('c') . " - Event ignored: {$eventType}\n",
        FILE_APPEND
    );
}

// Minden sikeres ágon válasz küldése
http_response_code(200);
echo "Webhook processed";
