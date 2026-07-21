<?php
/**
 * Professional Pharmacy Invoice Template
 * Called from pos_handler.php after successful billing
 * Variables available: $invoice_no, $customerName, $customerPhone,
 *   $payMethod, $itemRows, $subtotal, $discountAmt, $taxTotal,
 *   $grandTotal, $paidAmt, $balanceFinal, $printedBy, $currency
 */

$companyName  = getSetting('company_name',    'GM Hospital');
$companyAddr  = getSetting('company_address', '612, Nagarabhavi Main Rd, Vinayaka Layout, Bengaluru, Karnataka 560072');
$companyPhone = getSetting('company_phone',   '');
$companyGST   = getSetting('company_gstin',   '');
$footerNote   = getSetting('footer_note',     'Get well soon! Thank you for choosing us.');
$printTime    = date('d M Y, h:i A');

// ── Amount in Words ──────────────────────────────────────
function numToWords(float $n): string {
    $n = (int)round($n);
    if ($n <= 0) return 'Zero';
    $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine',
             'Ten','Eleven','Twelve','Thirteen','Fourteen','Fifteen','Sixteen',
             'Seventeen','Eighteen','Nineteen'];
    $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
    if ($n < 20)       return $ones[$n];
    if ($n < 100)      return $tens[(int)($n/10)] . ($n%10 ? ' '.$ones[$n%10] : '');
    if ($n < 1000)     return $ones[(int)($n/100)] . ' Hundred' . ($n%100 ? ' '.numToWords($n%100) : '');
    if ($n < 100000)   return numToWords((int)($n/1000)) . ' Thousand' . ($n%1000 ? ' '.numToWords($n%1000) : '');
    if ($n < 10000000) return numToWords((int)($n/100000)) . ' Lakh' . ($n%100000 ? ' '.numToWords($n%100000) : '');
    return numToWords((int)($n/10000000)) . ' Crore' . ($n%10000000 ? ' '.numToWords($n%10000000) : '');
}
$amtWords = numToWords((float)$grandTotal) . ' Rupees Only';

// ── Build Items Rows ─────────────────────────────────────
$itemsHtml = '';
foreach ($itemRows as $idx => $item) {
    $discAmt  = round($item['qty'] * $item['rate'] * $item['discount_percent'] / 100, 2);
    $discCell = $discAmt > 0 ? "-{$currency}" . number_format($discAmt, 2) : '—';
    $even     = ($idx % 2 === 1) ? "background:#f6fdfd;" : '';
    $itemsHtml .= "
    <tr style='{$even}'>
        <td style='text-align:center;color:#777;padding:7px 8px;'>" . ($idx + 1) . "</td>
        <td style='padding:7px 8px;'>
            <span style='font-weight:600;font-size:12px;'>" . htmlspecialchars($item['product_name']) . "</span>
            " . ($item['batch_no'] ? "<br><span style='font-size:9.5px;color:#999;'>Batch: " . htmlspecialchars($item['batch_no']) . "</span>" : "") . "
        </td>
        <td style='text-align:center;padding:7px 8px;'>{$item['qty']}</td>
        <td style='text-align:right;padding:7px 8px;'>{$currency}" . number_format($item['rate'], 2) . "</td>
        <td style='text-align:right;padding:7px 8px;color:#e53e3e;'>{$discCell}</td>
        <td style='text-align:right;padding:7px 8px;font-weight:700;'>{$currency}" . number_format($item['subtotal'], 2) . "</td>
    </tr>";
}
if (!$itemsHtml) {
    $itemsHtml = "<tr><td colspan='6' style='text-align:center;padding:20px;color:#aaa;'>No items</td></tr>";
}

$balLabel = $balanceFinal < 0 ? 'Balance Due' : 'Change';
$balColor = $balanceFinal < 0 ? '#e53e3e' : '#1f6b4a';

ob_start(); ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

<meta charset="UTF-8">
<title>Invoice <?= htmlspecialchars($invoice_no) ?></title>
<style>
*{box-sizing:border-box;margin:0;padding:0;}
body{font-family:'Segoe UI',Arial,sans-serif;font-size:12px;color:#222;background:#fff;padding:28px;max-width:800px;margin:0 auto;}
/* ── Header ── */
.inv-hdr{display:flex;justify-content:space-between;align-items:flex-start;padding-bottom:14px;border-bottom:3px solid #1f6b4a;margin-bottom:18px;}
.co-name{font-size:24px;font-weight:800;color:#1f6b4a;margin-bottom:4px;}
.co-addr{font-size:10.5px;color:#666;line-height:1.65;}
.inv-label{font-size:15px;font-weight:800;color:#222;text-transform:uppercase;letter-spacing:1.5px;text-align:right;}
.inv-no{font-size:13px;font-weight:700;color:#1f6b4a;text-align:right;margin:3px 0;}
.inv-date{font-size:11px;color:#888;text-align:right;}
/* ── Patient box ── */
.pt-box{background:#f5fbfc;border:1px solid #d0edf0;border-radius:8px;padding:12px 18px;margin-bottom:16px;display:grid;grid-template-columns:1fr 1fr;gap:7px 30px;}
.pf{display:flex;gap:8px;}
.pf-l{font-size:9px;font-weight:800;color:#1f6b4a;text-transform:uppercase;letter-spacing:.5px;min-width:78px;padding-top:1px;}
.pf-v{font-size:12px;font-weight:500;color:#222;}
/* ── Section title ── */
.sec-title{font-size:9.5px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:#777;margin-bottom:6px;}
/* ── Items table ── */
table.items{width:100%;border-collapse:collapse;margin-bottom:16px;font-size:11.5px;}
table.items thead tr{background:#1f6b4a;}
table.items thead th{color:#fff;padding:8px 8px;font-size:10px;font-weight:700;text-transform:uppercase;letter-spacing:.4px;}
table.items tbody td{border-bottom:1px solid #e8f4f5;}
/* ── Totals ── */
.tot-wrap{display:flex;justify-content:flex-end;margin-bottom:14px;}
.tot-inner{width:290px;border:1px solid #e0f0f2;border-radius:8px;overflow:hidden;}
.tot-row{display:flex;justify-content:space-between;padding:6px 14px;font-size:12px;border-bottom:1px solid #f0f0f0;}
.tot-row:last-child{border-bottom:none;}
.tot-grand{font-size:15px;font-weight:800;color:#1f6b4a;background:#f0fbfc;padding:8px 14px;}
.tot-paid{color:#1f6b4a;font-weight:700;}
.tot-bal-row{font-weight:700;}
/* ── Amount words ── */
.amt-words{background:#f5fbfc;border:1px solid #d0edf0;border-radius:6px;padding:9px 14px;font-size:11.5px;color:#333;margin-bottom:14px;}
.amt-words strong{display:block;font-size:9px;text-transform:uppercase;letter-spacing:.5px;color:#1f6b4a;margin-bottom:3px;}
/* ── Footer ── */
.inv-foot{display:flex;justify-content:space-between;align-items:flex-end;border-top:1px dashed #ccc;padding-top:10px;margin-top:4px;}
.foot-note{font-size:11px;color:#888;font-style:italic;}
.foot-print{font-size:10px;color:#aaa;text-align:right;line-height:1.6;}
.foot-print strong{color:#555;}
@media print{body{padding:10px;} @page{margin:10mm;} button{display:none!important;}}
</style>
</head>
<body>

<!-- HEADER -->
<div class="inv-hdr">
  <div>
    <div class="co-name"><?= htmlspecialchars($companyName) ?></div>
    <div class="co-addr">
      <?= nl2br(htmlspecialchars($companyAddr)) ?>
      <?php if ($companyPhone): ?><br>Phone: <?= htmlspecialchars($companyPhone) ?><?php endif; ?>
      <?php if ($companyGST): ?> &nbsp;|&nbsp; GSTIN: <?= htmlspecialchars($companyGST) ?><?php endif; ?>
    </div>
  </div>
  <div>
    <div class="inv-label">Pharmacy Invoice</div>
    <div class="inv-no"><?= htmlspecialchars($invoice_no) ?></div>
    <div class="inv-date"><?= date('d M Y') ?> &nbsp;&ndash;&nbsp; <?= date('H:i') ?></div>
  </div>
</div>

<!-- PATIENT DETAILS -->
<div class="pt-box">
  <div class="pf"><span class="pf-l">Patient</span><span class="pf-v"><?= htmlspecialchars($customerName) ?></span></div>
  <div class="pf"><span class="pf-l">Invoice No</span><span class="pf-v"><?= htmlspecialchars($invoice_no) ?></span></div>
  <div class="pf"><span class="pf-l">Phone</span><span class="pf-v"><?= $customerPhone ? htmlspecialchars($customerPhone) : '—' ?></span></div>
  <div class="pf"><span class="pf-l">Payment</span><span class="pf-v"><?= strtoupper(htmlspecialchars($payMethod)) ?></span></div>
  <div class="pf"><span class="pf-l">Date</span><span class="pf-v"><?= date('d M Y') ?></span></div>
  <div class="pf"><span class="pf-l">Created By</span><span class="pf-v"><?= htmlspecialchars($printedBy) ?></span></div>
</div>

<!-- ITEMS TABLE -->
<div class="sec-title">Billing Items</div>
<table class="items">
  <thead>
    <tr>
      <th style="width:32px;text-align:center;">#</th>
      <th style="text-align:left;">Description</th>
      <th style="width:52px;text-align:center;">Qty</th>
      <th style="width:95px;text-align:right;">Rate (<?= $currency ?>)</th>
      <th style="width:110px;text-align:right;">Discount (<?= $currency ?>)</th>
      <th style="width:105px;text-align:right;">Amount (<?= $currency ?>)</th>
    </tr>
  </thead>
  <tbody><?= $itemsHtml ?></tbody>
</table>

<!-- TOTALS -->
<div class="tot-wrap">
  <div class="tot-inner">
    <div class="tot-row"><span>Subtotal</span><span><?= $currency . number_format($subtotal, 2) ?></span></div>
    <div class="tot-row"><span>Discount</span><span style="color:#e53e3e;">-<?= $currency . number_format($discountAmt, 2) ?></span></div>
    <div class="tot-row"><span>Tax (GST)</span><span><?= $currency . number_format($taxTotal, 2) ?></span></div>
    <div class="tot-row tot-grand"><span>Grand Total</span><span><?= $currency . number_format($grandTotal, 2) ?></span></div>
    <div class="tot-row tot-paid"><span>Amount Paid</span><span><?= $currency . number_format($paidAmt, 2) ?></span></div>
    <div class="tot-row tot-bal-row" style="color:<?= $balColor ?>;"><span><?= $balLabel ?></span><span><?= $currency . number_format(abs($balanceFinal), 2) ?></span></div>
  </div>
</div>

<!-- AMOUNT IN WORDS -->
<div class="amt-words">
  <strong>Amount in Words</strong>
  <?= htmlspecialchars($amtWords) ?>
</div>

<!-- FOOTER -->
<div class="inv-foot">
  <div class="foot-note"><?= htmlspecialchars($footerNote) ?></div>
  <div class="foot-print">
    Printed on: <?= $printTime ?><br>
    Printed by: <strong><?= htmlspecialchars($printedBy) ?></strong>
  </div>
</div>

<script>window.onload=()=>setTimeout(()=>window.print(),400)</script>
</body>
</html>
<?php
return ob_get_clean();
