<?php
// InvoiceBuilder.php – kizárólag XML előállítására

use NavOnlineInvoice\InvoiceXmlBuilder;

class InvoiceBuilder {
    public static function buildInvoiceXml(array $stripeData): string {
        // csak AAM (0 %) logika
        $builder = new InvoiceXmlBuilder('3.0');

        $builder->addInvoice([
            'invoiceNumber' => $stripeData['invoiceNumber'],
            'invoiceIssueDate' => date('Y-m-d'),
            'invoiceDeliveryDate' => date('Y-m-d'),
            'currency' => $stripeData['currency'],
            'supplier' => [
                'name' => $_ENV['SELLER_NAME'],
                'taxNumber' => $_ENV['SELLER_TAX_NUMBER'],
                'address' => [
                    'postalCode' => $_ENV['SELLER_ADDRESS_POSTCODE'],
                    'city' => $_ENV['SELLER_ADDRESS_CITY'],
                    'streetName' => $_ENV['SELLER_ADDRESS_STREET'],
                    'countryCode' => $_ENV['SELLER_COUNTRY_CODE']
                ],
                'smallBusinessIndicator' => true // AAM jelzés
            ],
            'customer' => [
                'name' => $stripeData['customer']['name'],
                'taxNumber' => $stripeData['customer']['taxNumber'] ?? null,
                'address' => $stripeData['customer']['address']
            ],
            'items' => array_map(fn($i) => [
                'lineDescription' => $i['description'],
                'quantity' => $i['quantity'],
                'unitOfMeasure' => 'db',
                'unitPrice' => $i['amountNet'],
                'vatRate' => 'AAM'
            ], $stripeData['items'])
        ]);

        return $builder->asXml();
    }
}
