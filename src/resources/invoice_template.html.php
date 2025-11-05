<?php
// $data = $invoiceData az előző blokkból
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
  h1 { font-size: 18px; margin: 0 0 10px; }
  .row { display: flex; justify-content: space-between; }
  .box { padding: 8px; border: 1px solid #ccc; border-radius: 6px; }
  .mb-8 { margin-bottom: 8px; } .mb-12 { margin-bottom: 12px; } .mb-16 { margin-bottom: 16px; }
  table { width: 100%; border-collapse: collapse; }
  th, td { padding: 6px 8px; border-bottom: 1px solid #eee; text-align: left; }
  th { background: #f6f6f6; font-weight: 700; }
  .right { text-align: right; }
  .muted { color: #666; }
  .totals td { border: none; }
  .badge { display:inline-block; padding: 2px 6px; border:1px solid #999; border-radius:4px; font-size:10px; }
  footer { position: fixed; left: 0; right: 0; bottom: -10mm; height: 10mm; font-size: 10px; color: #666; }
</style>
</head>
<body>

<h1>Számla <span class="badge"><?= htmlspecialchars($data['totals']['vatRateLabel']) ?></span></h1>

<div class="row mb-12">
  <div class="box" style="width:49%;">
    <strong>Eladó</strong><br>
    <?= htmlspecialchars($data['seller']['name']) ?><br>
    Adószám: <?= htmlspecialchars($data['seller']['taxNumber']) ?><br>
    <?= htmlspecialchars($data['seller']['address']['postalCode']) ?> 
    <?= htmlspecialchars($data['seller']['address']['city']) ?>, 
    <?= htmlspecialchars($data['seller']['address']['street']) ?><br>
    <?= htmlspecialchars($data['seller']['address']['countryCode']) ?>
  </div>
  <div class="box" style="width:49%;">
    <strong>Vevő</strong><br>
    <?= htmlspecialchars($data['buyer']['name']) ?><br>
    <?php if (!empty($data['buyer']['taxNumber'])): ?>
      Adószám: <?= htmlspecialchars($data['buyer']['taxNumber']) ?><br>
    <?php else: ?>
      (Természetes személy)
    <?php endif; ?><br>
    <?= htmlspecialchars($data['buyer']['address']['postalCode']) ?>
    <?= htmlspecialchars($data['buyer']['address']['city']) ?>,
    <?= htmlspecialchars($data['buyer']['address']['street']) ?><br>
    <?= htmlspecialchars($data['buyer']['address']['countryCode']) ?><br>
    E-mail: <?= htmlspecialchars($data['buyer']['email']) ?>
  </div>
</div>

<div class="row mb-12">
  <div class="box" style="width:49%;">
    <strong>Számlaszám:</strong> <?= htmlspecialchars($data['invoiceNumber']) ?><br>
    <strong>Kiállítás dátuma:</strong> <?= htmlspecialchars($data['issueDate']) ?><br>
    <strong>Teljesítés dátuma:</strong> <?= htmlspecialchars($data['deliveryDate']) ?>
  </div>
  <div class="box" style="width:49%;">
    <strong>Fizetési mód:</strong> <?= htmlspecialchars($data['paymentMethod']) ?><br>
    <strong>Fizetési határidő:</strong> <?= htmlspecialchars($data['paymentDueDate']) ?><br>
    <span class="muted">Pénznem: <?= htmlspecialchars($data['currency']) ?></span>
  </div>
</div>

<table class="mb-16">
  <thead>
    <tr>
      <th>Tétel</th>
      <th class="right">Menny.</th>
      <th>Me.</th>
      <th class="right">Egységár (nettó)</th>
      <th class="right">Nettó sor</th>
      <th class="right">ÁFA</th>
      <th class="right">Bruttó sor</th>
    </tr>
  </thead>
  <tbody>
  <?php foreach ($data['items'] as $it): 
    $net = (float)$it['net'];
    $qty = (float)($it['qty'] ?? 1);
    $unit = $it['unit'] ?? 'db';
    $lineNet = $net * $qty;
    $vat = 0.00; // AAM
    $gross = $lineNet + $vat;
  ?>
    <tr>
      <td><?= htmlspecialchars($it['desc']) ?></td>
      <td class="right"><?= $fmt($qty) ?></td>
      <td><?= htmlspecialchars($unit) ?></td>
      <td class="right"><?= $fmt($net) ?></td>
      <td class="right"><?= $fmt($lineNet) ?></td>
      <td class="right">0,00</td>
      <td class="right"><?= $fmt($gross) ?></td>
    </tr>
  <?php endforeach; ?>
  </tbody>
</table>

<table class="totals" style="width: 60%; float: right;">
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
    <td class="right"><strong><?= $fmt($data['totals']['gross']) ?> <?= htmlspecialchars($data['currency']) ?></strong></td>
  </tr>
</table>

<div style="clear: both;"></div>

<p class="muted">
  Megjegyzés: AAM – alanyi adómentes (Áfa tv. szerint). A feltüntetett árak ÁFA-t nem tartalmaznak.
</p>

<footer>
  <?= htmlspecialchars($data['seller']['name']) ?> • Adószám: <?= htmlspecialchars($data['seller']['taxNumber']) ?> • 
  <?= htmlspecialchars($data['seller']['address']['postalCode']) ?> <?= htmlspecialchars($data['seller']['address']['city']) ?>, <?= htmlspecialchars($data['seller']['address']['street']) ?>
</footer>

</body>
</html>
