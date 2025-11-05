<?php
class InvoiceBuilder {

    public static function buildInvoiceXml(array $stripeData): string {
        $seller = [
            'name' => $_ENV['SELLER_NAME'],
            'taxNumber' => $_ENV['SELLER_TAX_NUMBER'],
            'address' => [
                'countryCode' => $_ENV['SELLER_COUNTRY_CODE'],
                'postalCode' => $_ENV['SELLER_ADDRESS_POSTCODE'],
                'city' => $_ENV['SELLER_ADDRESS_CITY'],
                'street' => $_ENV['SELLER_ADDRESS_STREET']
            ]
        ];

        $buyer = $stripeData['customer'];
        $items = $stripeData['items'];
        $currency = strtoupper($stripeData['currency']);
        $invoiceNumber = $stripeData['invoiceNumber'] ?? ('TEST-' . time());

        // XML skeleton
        $xml = new SimpleXMLElement('<InvoiceData xmlns="http://schemas.nav.gov.hu/OSA/3.0/api" />');
        $invoiceMain = $xml->addChild('invoiceMain');
        $invoice = $invoiceMain->addChild('invoice');

        // Fejléc
        $header = $invoice->addChild('invoiceHead');
        $header->addChild('invoiceNumber', htmlspecialchars($invoiceNumber));
        $header->addChild('invoiceIssueDate', date('Y-m-d'));
        $header->addChild('invoiceDeliveryDate', date('Y-m-d'));
        $header->addChild('invoiceCategory', 'NORMAL');
        $header->addChild('invoiceDeliveryMode', 'ELECTRONIC');
        $header->addChild('invoiceCurrencyCode', $currency);

        // Eladó adatai
        $supplier = $invoice->addChild('supplierInfo');
        $supplier->addChild('supplierName', htmlspecialchars($seller['name']));
        $supplierTax = $supplier->addChild('supplierTaxNumber');
        $supplierTax->addChild('taxpayerId', $seller['taxNumber']);
        $supplierAddress = $supplier->addChild('supplierAddress');
        foreach ($seller['address'] as $k => $v) {
            $supplierAddress->addChild($k, htmlspecialchars($v));
        }

        // Vevő adatai
        $customerXml = $invoice->addChild('customerInfo');
        $customerXml->addChild('customerName', htmlspecialchars($buyer['name']));
        if (!empty($buyer['taxNumber'])) {
            $taxEl = $customerXml->addChild('customerTaxNumber');
            $taxEl->addChild('taxpayerId', $buyer['taxNumber']);
        }
        if (!empty($buyer['email'])) {
            $customerXml->addChild('customerEmailAddress', htmlspecialchars($buyer['email']));
        }
        $customerAddress = $customerXml->addChild('customerAddress');
        foreach ($buyer['address'] as $k => $v) {
            $customerAddress->addChild($k, htmlspecialchars($v));
        }

        // Tételsorok
        $lines = $invoice->addChild('invoiceLines');
        $totalNet = 0; $totalGross = 0;
        foreach ($items as $i => $item) {
            $line = $lines->addChild('line');
            $line->addChild('lineNumber', $i + 1);
            $line->addChild('lineDescription', htmlspecialchars($item['description']));
            $line->addChild('quantity', $item['quantity']);
            $line->addChild('unitOfMeasure', 'PIECE');
            $line->addChild('unitPrice', number_format($item['amountNet'], 2, '.', ''));
            $line->addChild('lineNetAmount', number_format($item['amountNet'], 2, '.', ''));
            $line->addChild('lineVatRate', 'AAM'); // Alanyi adómentes
            $line->addChild('lineGrossAmount', number_format($item['amountNet'], 2, '.', ''));

            $totalNet += $item['amountNet'];
            $totalGross += $item['amountNet'];
        }

        // Összesítő
        $summary = $invoice->addChild('invoiceSummary');
        $vatSummary = $summary->addChild('summaryByVatRate');
        $vatSummary->addChild('vatRate', 'AAM');
        $vatSummary->addChild('vatRateNetAmount', number_format($totalNet, 2, '.', ''));
        $vatSummary->addChild('vatRateVatAmount', '0.00');
        $vatSummary->addChild('vatRateGrossAmount', number_format($totalGross, 2, '.', ''));
        $summary->addChild('invoiceNetAmount', number_format($totalNet, 2, '.', ''));
        $summary->addChild('invoiceGrossAmount', number_format($totalGross, 2, '.', ''));

        return $xml->asXML();
    }
}
