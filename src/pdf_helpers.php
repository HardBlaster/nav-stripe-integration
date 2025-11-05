<?php
use Dompdf\Dompdf;
use Dompdf\Options;

function generateInvoicePdf(array $invoiceData, string $outputPath): string {
    // 1) HTML render (PHP template)
    ob_start();
    $data = $invoiceData;
    include __DIR__ . '/../resources/invoice_template.html.php';
    $html = ob_get_clean();

    // 2) Dompdf beállítások (A4, ékezet)
    $options = new Options();
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'DejaVu Sans');
    $dompdf = new Dompdf($options);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->render();

    // 3) Fájlba írás
    $pdf = $dompdf->output();
    file_put_contents($outputPath, $pdf);
    return $outputPath;
}
