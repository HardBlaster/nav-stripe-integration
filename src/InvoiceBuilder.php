<?php
use NavOnlineInvoice\InvoiceXmlBuilder;

class InvoiceBuilder {
    public static function buildInvoiceXml(array $stripeData): string
    {
        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><InvoiceData/>');
        $xml->addAttribute('xmlns', 'http://schemas.nav.gov.hu/OSA/3.0/data');

        // Fő elemek
        $xml->addChild('invoiceNumber', htmlspecialchars($stripeData['invoiceNumber']));
        $xml->addChild('invoiceIssueDate', date('Y-m-d'));
        $xml->addChild('completenessIndicator', 'false');

        // invoiceMain
        $invoiceMain = $xml->addChild('invoiceMain');
        $invoice = $invoiceMain->addChild('invoice');

        // ===== Fejléc (invoiceHead) =====
        $head = $invoice->addChild('invoiceHead');

        // --- Eladó
        $supplier = $head->addChild('supplierInfo');
        $taxNum = $supplier->addChild('supplierTaxNumber');
        $taxNum->addChild('base:taxpayerId', preg_replace('/[^0-9]/', '', $_ENV['SELLER_TAX_NUMBER'] ?? '99999999'), 'http://schemas.nav.gov.hu/OSA/3.0/base');
        $taxNum->addChild('base:vatCode', '2', 'http://schemas.nav.gov.hu/OSA/3.0/base');
        $taxNum->addChild('base:countyCode', '41', 'http://schemas.nav.gov.hu/OSA/3.0/base');
        $supplier->addChild('supplierName', htmlspecialchars($_ENV['SELLER_NAME'] ?? 'Cégem Kft'));

        // Cím – NAV szerinti bontás
        $sAddr = $supplier->addChild('supplierAddress');
        $dAddr = $sAddr->addChild('base:detailedAddress', null, 'http://schemas.nav.gov.hu/OSA/3.0/base');
        $dAddr->addChild('base:countryCode', $_ENV['SELLER_COUNTRY_CODE'] ?? 'HU');
        $dAddr->addChild('base:postalCode', $_ENV['SELLER_ADDRESS_POSTCODE'] ?? '1111');
        $dAddr->addChild('base:city', $_ENV['SELLER_ADDRESS_CITY'] ?? 'Budapest');
        $dAddr->addChild('base:streetName', $_ENV['SELLER_ADDRESS_STREET'] ?? 'Dandár');
        $dAddr->addChild('base:publicPlaceCategory', $_ENV['SELLER_ADDRESS_PUBLIC_PLACE_CATEGORY'] ?? 'utca');
        $dAddr->addChild('base:number', $_ENV['SELLER_ADDRESS_NUMBER'] ?? '17');
        $supplier->addChild('supplierBankAccountNumber', $_ENV['SELLER_BANK'] ?? '12345678-12345678-12345678');

        // --- Vevő
        $buyer = $stripeData['customer'] ?? [];
        $cust = $head->addChild('customerInfo');
        $cust->addChild('customerVatStatus', empty($buyer['taxNumber']) ? 'PRIVATE_PERSON' : 'DOMESTIC');

        if (!empty($buyer['taxNumber'])) {
            $vatData = $cust->addChild('customerVatData');
            $ctax = $vatData->addChild('customerTaxNumber');
            $ctax->addChild('base:taxpayerId', preg_replace('/[^0-9]/', '', $buyer['taxNumber']) ?? '12345678');
            $ctax->addChild('base:vatCode', '2');
            $ctax->addChild('base:countyCode', '41');
        }

        $cust->addChild('customerName', htmlspecialchars($buyer['name'] ?? 'Ismeretlen vevő'));
        $cAddr = $cust->addChild('customerAddress');
        $cdAddr = $cAddr->addChild('base:simpleAddress', null, 'http://schemas.nav.gov.hu/OSA/3.0/base');
        $bAddr = $buyer['address'] ?? [];
        $cdAddr->addChild('base:countryCode', $bAddr['countryCode']);
        $cdAddr->addChild('base:postalCode', $bAddr['postalCode']);
        $cdAddr->addChild('base:city', $bAddr['city']);
        $cdAddr->addChild('base:additionalAddressDetail', $bAddr['street']);

        // --- Invoice Detail
        $detail = $head->addChild('invoiceDetail');
        $detail->addChild('invoiceCategory', 'SIMPLIFIED');
        $detail->addChild('invoiceDeliveryDate', date('Y-m-d'));
        $detail->addChild('currencyCode', strtoupper($stripeData['currency'] ?? 'HUF'));
        $detail->addChild('exchangeRate', '1');
        $detail->addChild('paymentMethod', 'CARD');
        $detail->addChild('cashAccountingIndicator', 'true');
        $detail->addChild('invoiceAppearance', 'ELECTRONIC');

        // ===== Tételsorok (invoiceLines) =====
        $lines = $invoice->addChild('invoiceLines');
        $lines->addChild('mergedItemIndicator', 'false');

        $sumGross = 0.0;
        $idx = 1;
        foreach ($stripeData['items'] as $item) {
            $qty = (float)($item['quantity'] ?? 1);
            $unitPrice = (float)($item['amountNet'] ?? 0);
            $gross = $qty * $unitPrice;
            $sumGross += $gross;

            $line = $lines->addChild('line');
            $line->addChild('lineNumber', $idx++);
            $line->addChild('lineExpressionIndicator', 'true');
            $line->addChild('lineNatureIndicator', 'SERVICE');
            $line->addChild('lineDescription', htmlspecialchars($item['description'] ?? 'Termék'));

            $line->addChild('quantity', self::dec($qty));
            $line->addChild('unitOfMeasure', 'PIECE');
            $line->addChild('unitPrice', self::dec($unitPrice));
            $line->addChild('unitPriceHUF', self::dec($unitPrice));

            $lineAmounts = $line->addChild('lineAmountsSimplified');
            $vatRate = $lineAmounts->addChild('lineVatRate');
            $vatEx = $vatRate->addChild('vatExemption');
            $vatEx->addChild('case', 'AAM');
            $vatEx->addChild('reason', 'Alanyi adómentes');
            $lineAmounts->addChild('lineGrossAmountSimplified', self::dec($gross));
            $lineAmounts->addChild('lineGrossAmountSimplifiedHUF', self::dec($gross));
        }

        // ===== Összesítés (invoiceSummary) =====
        $sum = $invoice->addChild('invoiceSummary');
        $summaryAam = $sum->addChild('summarySimplified');
        $vatRate = $summaryAam->addChild('vatRate');
        $vatEx = $vatRate->addChild('vatExemption');
        $vatEx->addChild('case', 'AAM');
        $vatEx->addChild('reason', 'Alanyi adómentes');
        $summaryAam->addChild('vatContentGrossAmount', self::dec($sumGross));
        $summaryAam->addChild('vatContentGrossAmountHUF', self::dec($sumGross));

        $grossData = $sum->addChild('summaryGrossData');
        $grossData->addChild('invoiceGrossAmount', self::dec($sumGross));
        $grossData->addChild('invoiceGrossAmountHUF', self::dec($sumGross));

        return $xml->asXML();
    }

    private static function dec(float $n): string
    {
        return number_format($n, 2, '.', '');
    }
}
