<?php
$fmt = fn($n) => number_format($n, 2, ',', ' ');
?>
<!DOCTYPE html>
<html lang="hu">
<head>
<meta charset="utf-8">
<title>Számla <?= htmlspecialchars($data['invoiceNumber']) ?></title>
<style>
  @page { size: A4; margin: 18mm 15mm 20mm 15mm; }
  body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
  h1 { font-size: 20px; text-align: right; color: #222; margin-bottom: 5px; }
  .header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 10px; }
  .seller { width: 55%; }
  .bank { width: 40%; text-align: right; }
  .section { margin-bottom: 14px; }
  .box { border: 1px solid #ddd; border-radius: 6px; padding: 10px; }
  .label { font-weight: bold; display: inline-block; min-width: 120px; }
  .row { display: flex; justify-content: space-between; margin-bottom: 6px; }
  table { width: 100%; border-collapse: collapse; margin-top: 10px; }
  th, td { padding: 6px 8px; border-bottom: 1px solid #eee; text-align: left; }
  th { background: #f6f8fb; font-weight: bold; }
  .right { text-align: right; }
  .muted { color: #666; }
  .highlight { color: #007bff; font-weight: bold; }
  .totals td { border: none; font-size: 12px; }
  footer { position: fixed; left: 0; right: 0; bottom: -10mm; height: 10mm; font-size: 10px; color: #666; text-align: center; }
</style>
</head>
<body>

<!-- === HEADER === -->
<div class="header">
  <div class="seller">
    <strong><?= htmlspecialchars($data['seller']['name']) ?></strong><br>
    <?= htmlspecialchars($data['seller']['address']['postalCode']) ?> 
    <?= htmlspecialchars($data['seller']['address']['city']) ?>,
    <?= htmlspecialchars($data['seller']['address']['street']) ?><br>
    Adószám: <?= htmlspecialchars($data['seller']['taxNumber']) ?><br>
    <?php if (!empty($data['seller']['phone'])): ?>
      Tel: <?= htmlspecialchars($data['seller']['phone']) ?><br>
    <?php endif; ?>
  </div>
  <div class="bank">
    <?php if (!empty($data['seller']['bankName'])): ?>
      Bank neve: <?= htmlspecialchars($data['seller']['bankName']) ?><br>
    <?php endif; ?>
    <?php if (!empty($data['seller']['iban'])): ?>
      Bankszámlaszám: <?= htmlspecialchars($data['seller']['iban']) ?><br>
    <?php endif; ?>
  </div>
</div>

<h1>SZÁMLA</h1>
<p style="text-align:right; margin-top:-5px; font-size:12px;">Sorszám: <strong><?= htmlspecialchars($data['invoiceNumber']) ?></strong></p>

<!-- === BUYER + PAYMENT BLOCK === -->
<div class="section" style="display: flex; justify-content: space-between;">
  <div style="width: 48%;" class="box">
    <strong>Vevő</strong><br>
    <?= htmlspecialchars($data['buyer']['name']) ?><br>
    <?php if (!empty($data['buyer']['taxNumber'])): ?>
      Adószám: <?= htmlspecialchars($data['buyer']['taxNumber']) ?><br>
    <?php else: ?>
      (Természetes személy)<br>
    <?php endif; ?>
    <?= htmlspecialchars($data['buyer']['address']['postalCode']) ?> 
    <?= htmlspecialchars($data['buyer']['address']['city']) ?>,
    <?= htmlspecialchars($data['buyer']['address']['street']) ?><br>
    <?= htmlspecialchars($data['buyer']['address']['countryCode']) ?><br>
    E-mail: <?= htmlspecialchars($data['buyer']['email']) ?>
  </div>

  <div style="width: 48%;" class="box">
    <strong>Fizetési információk</strong><br>
    <span class="label">Fizetési mód:</span> <?= htmlspecialchars($data['paymentMethod']) ?><br>
    <span class="label">Teljesítés dátuma:</span> <?= htmlspecialchars($data['deliveryDate']) ?><br>
    <span class="label">Kiállítás dátuma:</span> <?= htmlspecialchars($data['issueDate']) ?><br>
    <span class="label">Fizetési határidő:</span> <span class="highlight"><?= htmlspecialchars($data['paymentDueDate']) ?></span><br>
    <span class="muted">Pénznem: <?= htmlspecialchars($data['currency']) ?></span>
  </div>
</div>

<!-- === ITEMS TABLE === -->
<table>
  <thead>
    <tr>
      <th>Megnevezés</th>
      <th class="right">Menny.</th>
      <th>Me.</th>
      <th class="right">Egységár (nettó)</th>
      <th class="right">Nettó ár</th>
      <th class="right">ÁFA</th>
      <th class="right">ÁFA érték</th>
      <th class="right">Bruttó ár</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($data['items'] as $it): 
    $qty = (float)($it['qty'] ?? 1);
    $unit = $it['unit'] ?? 'db';
    $net = (float)$it['net'];
    $lineNet = $net * $qty;
    $vatRate = $it['vatRate'] ?? 0;
    $vat = $lineNet * ($vatRate / 100);
    $gross = $lineNet + $vat;
    $vatLabel = $vatRate > 0 ? $vatRate.'%' : 'AAM';
  ?>
    <tr>
      <td><?= htmlspecialchars($it['desc']) ?></td>
      <td class="right"><?= $fmt($qty) ?></td>
      <td><?= htmlspecialchars($unit) ?></td>
      <td class="right"><?= $fmt($net) ?></td>
      <td class="right"><?= $fmt($lineNet) ?></td>
      <td class="right"><?= htmlspecialchars($vatLabel) ?></td>
      <td class="right"><?= $fmt($vat) ?></td>
      <td class="right"><?= $fmt($gross) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<!-- === TOTALS === -->
<table class="totals" style="width: 40%; float: right; margin-top: 10px;">
  <tr>
    <td class="right muted">Összesen (nettó):</td>
    <td class="right"><strong><?= $fmt($data['totals']['net']) ?> <?= htmlspecialchars($data['currency']) ?></strong></td>
  </tr>
  <tr>
    <td class="right muted">ÁFA összesen (<?= htmlspecialchars($data['totals']['vatRateLabel']) ?>):</td>
    <td class="right"><strong><?= $fmt($data['totals']['vat']) ?> <?= htmlspecialchars($data['currency']) ?></strong></td>
  </tr>
  <tr>
    <td class="right muted">Végösszeg (bruttó):</td>
    <td class="right highlight"><strong><?= $fmt($data['totals']['gross']) ?> <?= htmlspecialchars($data['currency']) ?></strong></td>
  </tr>
</table>

<div style="clear: both;"></div>

<!-- === NOTES === -->
<p class="muted" style="margin-top: 20px;">
  Megjegyzés: AAM – alanyi adómentes (Áfa tv. szerint). A feltüntetett árak ÁFA-t nem tartalmaznak.
</p>
<p class="muted">A számla aláírás és bélyegző nélkül is érvényes.</p>

<footer>
  <?= htmlspecialchars($data['seller']['name']) ?> • Adószám: <?= htmlspecialchars($data['seller']['taxNumber']) ?> • 
  <?= htmlspecialchars($data['seller']['address']['postalCode']) ?> <?= htmlspecialchars($data['seller']['address']['city']) ?>, <?= htmlspecialchars($data['seller']['address']['street']) ?>
</footer>

</body>
</html>