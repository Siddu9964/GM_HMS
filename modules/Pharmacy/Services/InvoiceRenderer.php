<?php
namespace GM_HMS\Modules\Pharmacy\Services;

/**
 * InvoiceRenderer
 * Generates a fully formatted, printable HTML invoice for pharmacy billing.
 * Supports both A4 Portrait and A5 Portrait/Landscape toggle.
 * Fixed: TOTAL column truncation, patient label cutoff, A4/A5 @page CSS,
 *        table-layout:fixed, correct number_format() on all monetary values.
 */
class InvoiceRenderer {

    /**
     * Build invoice HTML for printing
     */
    public function render(array $m, array $items, string $printedBy = 'Pharmacist'): string {
        $c      = '&#x20B9;'; // ₹ as HTML entity (safe in all contexts)
        $inv    = htmlspecialchars($m['invoice_no'] ?? '');
        $cName  = htmlspecialchars($m['customer_name'] ?? 'Walk-in');
        $cPhone = htmlspecialchars($m['customer_phone'] ?? '—');
        $cAge   = htmlspecialchars($m['customer_age'] ?? '—');
        $cSex   = htmlspecialchars($m['customer_sex'] ?? '—');
        $paymentMethod = strtoupper(htmlspecialchars($m['payment_method'] ?? 'CASH'));

        $sub   = (float)($m['subtotal']        ?? 0);
        $disc  = (float)($m['discount_amount'] ?? 0);
        $tax   = (float)($m['tax_total']       ?? 0);
        $grand = (float)($m['grand_total']     ?? 0);
        $paid  = (float)($m['paid_amount']     ?? $grand);
        $bal   = round($paid - $grand, 2);

        // Pre-formatted strings for heredoc (number_format not usable inside heredoc)
        $fSub   = number_format($sub,   2);
        $fDisc  = number_format($disc,  2);
        $fTax   = number_format($tax,   2);
        $fGrand = number_format($grand, 2);
        $fPaid  = number_format($paid,  2);
        $fBal   = number_format(abs($bal), 2);

        // Recalculate balance label & colour
        $bLbl = $bal < 0 ? 'Balance Due' : 'Change';
        $bClr = $bal < 0 ? '#dc2626'    : '#16a34a';

        // Amount in words
        $wrds = $this->numToWords((float)$grand) . ' Rupees Only';

        // Invoice date / time
        $invDate = !empty($m['invoice_date'])
            ? date('d M Y', strtotime($m['invoice_date']))
            : date('d M Y');
        $invTime = $m['invoice_time'] ?? date('H:i:s');
        // Format time to 12-hour
        $invTimeFormatted = date('h:i A', strtotime($invTime));

        // ── Doctor name lookup ─────────────────────────────────────────────
        $doctorName = 'Self / Walk-in';
        if (!empty($m['doctor_name'])) {
            $dn = trim($m['doctor_name']);
            $doctorName = (stripos($dn, 'Dr.') === 0 || stripos($dn, 'Dr ') === 0) ? $dn : 'Dr. ' . $dn;
        } elseif (!empty($m['customer_id'])) {
            try {
                $db = \GM_HMS\Database\SecureDatabase::getInstance();
                $docRow = $db->fetchOne(
                    "SELECT COALESCE(d.full_name, c.doctor_id) AS doctor_name
                     FROM consultations c
                     LEFT JOIN doctors d ON d.doctor_id = c.doctor_id
                     WHERE c.patient_id = ?
                     ORDER BY c.consultation_date DESC, c.consultation_time DESC
                     LIMIT 1",
                    [$m['customer_id']]
                );
                if ($docRow && !empty($docRow['doctor_name'])) {
                    $dn = trim($docRow['doctor_name']);
                    $doctorName = (stripos($dn, 'Dr.') === 0 || stripos($dn, 'Dr ') === 0) ? $dn : 'Dr. ' . $dn;
                }
            } catch (\Exception $e) { /* keep default */ }
        }

        // ── Build item rows ────────────────────────────────────────────────
        $rows        = '';
        $receiptRows = '';  // compact list for Receipt (80mm) mode
        $totalTaxAmt = 0;
        foreach ($items as $idx => $item) {
            $rate      = (float)($item['rate']             ?? 0);
            $qty       = (int)  ($item['qty']              ?? 0);
            $dp        = (float)($item['discount_percent'] ?? 0);
            $gst_pct   = (float)($item['tax_percent']      ?? 12);
            $gst_amt   = (float)($item['tax_amount']       ?? 0);

            // Line total: use stored 'total' → 'subtotal' → or recalculate
            $lineTotal = (float)($item['total'] ?? $item['subtotal'] ?? ($rate * $qty));

            $cgst_pct  = $gst_pct / 2;
            $sgst_pct  = $gst_pct / 2;
            $cgst_amt  = round($gst_amt / 2, 2);
            $sgst_amt  = round($gst_amt / 2, 2);
            $totalTaxAmt += $gst_amt;

            // Expiry formatted as MM/YY
            $expStr = '—';
            if (!empty($item['expiry_date'])
                && $item['expiry_date'] !== '0000-00-00'
                && $item['expiry_date'] !== '') {
                $expStr = date('m/y', strtotime($item['expiry_date']));
            }

            $mfgHtml = !empty($item['manufacturer'])
                ? '<div class="item-mfg">Mfg: ' . htmlspecialchars($item['manufacturer']) . '</div>'
                : '';

            $bg = $idx % 2 === 0 ? '#ffffff' : '#f8fafc';

            // ── Normal wide-table row (A4 / A5)
            $rows .= '
            <tr style="background:' . $bg . '">
              <td class="cell-sl">'   . ($idx + 1) . '</td>
              <td class="cell-desc">' . htmlspecialchars($item['product_name']) . $mfgHtml . '</td>
              <td class="cell-c">'    . htmlspecialchars($item['hsn_code']  ?: '—') . '</td>
              <td class="cell-c">'    . htmlspecialchars($item['batch_no']  ?: ($item['batch_number'] ?? '—')) . '</td>
              <td class="cell-c">'    . $expStr . '</td>
              <td class="cell-c bold">' . $qty . '</td>
              <td class="cell-r">'    . number_format($rate, 2) . '</td>
              <td class="cell-c muted">' . number_format($dp, 1) . '%</td>
              <td class="cell-c muted">' . number_format($cgst_pct, 1) . '%</td>
              <td class="cell-r muted">' . number_format($cgst_amt, 2) . '</td>
              <td class="cell-c muted">' . number_format($sgst_pct, 1) . '%</td>
              <td class="cell-r muted">' . number_format($sgst_amt, 2) . '</td>
              <td class="cell-r cell-total">' . number_format($lineTotal, 2) . '</td>
            </tr>';

            // ── Compact receipt row (80mm thermal mode)
            $batchStr = htmlspecialchars($item['batch_no'] ?: ($item['batch_number'] ?? ''));
            $receiptRows .= '
            <div style="padding:3px 0;border-bottom:1px dashed #e2e8f0;">
              <div style="display:flex;justify-content:space-between;align-items:flex-start;">
                <div style="flex:1;font-weight:700;font-size:8.5px;color:#1e293b;">'
                    . ($idx + 1) . '. ' . htmlspecialchars($item['product_name']) .
                '</div>
                <div style="font-weight:800;font-size:9px;color:#145036;white-space:nowrap;padding-left:6px;">&#x20B9;' . number_format($lineTotal, 2) . '</div>
              </div>
              <div style="color:#64748b;font-size:7px;margin-top:1px;">
                Qty: <strong>' . $qty . '</strong> &times; &#x20B9;' . number_format($rate, 2) .
                ($batchStr ? ' &nbsp;|&nbsp; Batch: ' . $batchStr : '') .
                ' &nbsp;|&nbsp; Exp: ' . $expStr .
                ' &nbsp;|&nbsp; GST: ' . number_format($gst_pct, 0) . '% (CGST ' . number_format($cgst_pct, 1) . '% + SGST ' . number_format($sgst_pct, 1) . '%)' .
                ($mfgHtml ? '<br>' . strip_tags($mfgHtml) : '') .
              '</div>
            </div>';
        }

        // ── HTML Output ────────────────────────────────────────────────────
        return <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Invoice {$inv} — BASAVESHWAR NAGAR PHARMA</title>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800;900&display=swap" rel="stylesheet">

<!-- Page-size style — updated dynamically by toggle button -->
<!-- Default: no forced size, so printer uses its loaded paper (avoids mismatch dialog) -->
<style id="page-size-style">
  @page { margin: 10mm; }
</style>

<style>
/* ═══════════════════════════════════════════
   CSS CUSTOM PROPERTIES
═══════════════════════════════════════════ */
:root {
  --primary      : #1f6b4a;
  --primary-dk   : #145036;
  --accent       : #0891b2;
  --red          : #dc2626;
  --text-main    : #1e293b;
  --text-muted   : #64748b;
  --bg-light     : #f8fafc;
  --bg-mid       : #f1f5f9;
  --border       : #e2e8f0;
  --border-mid   : #cbd5e1;
}

/* ═══════════════════════════════════════════
   RESET & BASE
═══════════════════════════════════════════ */
*, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

body {
  font-family: 'Inter', -apple-system, sans-serif;
  color: var(--text-main);
  font-size: 9.5px;
  line-height: 1.45;
  -webkit-print-color-adjust: exact;
  print-color-adjust: exact;
}

/* ═══════════════════════════════════════════
   SCREEN ONLY — preview wrapper
═══════════════════════════════════════════ */
@media screen {
  body {
    background: #dde3ec;
    padding: 6px 16px 20px;   /* minimal top padding — no dead space */
    display: flex;
    flex-direction: column;
    align-items: center;
    min-height: 100vh;
  }
  .invoice-wrapper {
    width: 100%;
    max-width: 860px;
  }
  .action-bar {
    display: flex;
    justify-content: flex-end;
    align-items: center;
    gap: 10px;
    margin-bottom: 8px;       /* tight gap between bar and invoice */
    flex-wrap: wrap;
    padding-top: 4px;
  }
  .size-label {
    font-size: 11px;
    color: var(--text-muted);
    font-weight: 600;
    background: #fff;
    padding: 4px 10px;
    border-radius: 20px;
    border: 1px solid var(--border);
    margin-right: auto;
  }
  .btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 7px 14px;
    border-radius: 8px;
    font-weight: 700;
    font-size: 11.5px;
    cursor: pointer;
    border: none;
    font-family: 'Inter', sans-serif;
    transition: all 0.18s ease;
  }
  .btn-print {
    background: var(--primary);
    color: #fff;
    box-shadow: 0 4px 12px rgba(31,107,74,0.28);
  }
  .btn-print:hover { background: var(--primary-dk); transform: translateY(-1px); }
  .btn-a5 { background: #1e40af; color: #fff; }
  .btn-a5:hover { background: #1d3a8a; }
  .btn-close { background: #fff; color: var(--text-muted); border: 1px solid var(--border); }
  .btn-close:hover { background: var(--bg-light); color: var(--text-main); }
  .invoice-container {
    background: #fff;
    padding: 20px 22px;
    border-radius: 14px;
    box-shadow: 0 8px 40px rgba(0,0,0,0.10);
  }
}

/* ═══════════════════════════════════════════
   PRINT ONLY
═══════════════════════════════════════════ */
@media print {
  body { background: #fff !important; padding: 0; }
  .action-bar  { display: none !important; }
  /* On print: position:fixed puts watermark on every page, centered on page */
  .watermark {
    position: fixed !important;
    top: 50% !important;
    left: 50% !important;
    color: rgba(31,107,74,0.12) !important;
    font-size: 110px !important;
  }
  .invoice-container {
    padding: 15px 15px 0 15px !important; /* Add padding all around to prevent hardware edge cutoff */
    box-shadow: none;
    border-radius: 0;
    overflow: visible; /* allow watermark to show on print */
  }
}

/* ═══════════════════════════════════════════
   WATERMARK
   — Screen: position:absolute inside .invoice-container
     (overflow:hidden clips it neatly inside the card)
   — Print:  overridden above to position:fixed so it
     appears centered on every printed page
═══════════════════════════════════════════ */
.watermark {
  position: absolute;           /* stays inside the invoice card on screen */
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-35deg);
  font-size: 100px;
  font-weight: 900;
  color: rgba(31,107,74,0.07);
  z-index: 0;
  pointer-events: none;
  white-space: nowrap;
  user-select: none;
  letter-spacing: 6px;
  width: 120%;                  /* wide enough so rotated text fills the card */
  text-align: center;
}

/* ═══════════════════════════════════════════
   INVOICE CONTAINER
   — position:relative  → establishes stacking context for absolute watermark
   — overflow:hidden    → clips the rotated/oversized watermark to the card
═══════════════════════════════════════════ */
.invoice-container {
  position: relative;
  z-index: 1;
  overflow: hidden;             /* clips oversized rotated watermark on screen */
}
.invoice-container > *:not(.watermark) { position: relative; z-index: 2; }

/* ═══════════════════════════════════════════
   HEADER
═══════════════════════════════════════════ */
.hdr {
  text-align: center;
  padding-bottom: 8px;
  margin-bottom: 8px;
  border-bottom: 2px solid var(--border);
}
.hdr-name {
  font-size: 15px;
  font-weight: 900;
  color: var(--primary);
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 2px;
}
.hdr-sub  { font-size: 8.5px; font-weight: 700; color: var(--text-main); margin-bottom: 1px; }
.hdr-addr { font-size: 8.5px; color: var(--text-muted); margin-bottom: 1px; }
.hdr-dl   { font-size: 8px;   color: var(--text-muted); font-weight: 600; margin-bottom: 3px; }
.hdr-gst  {
  display: inline-block;
  font-size: 9px; font-weight: 800;
  color: var(--text-main);
  background: var(--bg-light);
  border: 1px solid var(--border-mid);
  padding: 1px 10px;
  border-radius: 20px;
}
.invoice-title {
  display: inline-block;
  font-size: 11px;
  font-weight: 800;
  color: #fff;
  background: var(--primary);
  padding: 2px 14px;
  border-radius: 20px;
  margin-top: 5px;
  letter-spacing: 1px;
  text-transform: uppercase;
}

/* ═══════════════════════════════════════════
   PATIENT INFO GRID — 2 rows × 3 cols
═══════════════════════════════════════════ */
.pt-grid {
  display: grid;
  grid-template-columns: 1fr 1fr 1fr;
  gap: 4px 12px;
  padding: 7px 10px;
  margin-bottom: 8px;
  background: var(--bg-light);
  border: 1px solid var(--border);
  border-radius: 6px;
}
.pt-field { display: flex; flex-direction: row; align-items: flex-start; gap: 3px; min-width: 0; }
.pt-label {
  font-size: 8px;
  font-weight: 800;
  color: var(--text-muted);
  text-transform: uppercase;
  letter-spacing: 0.4px;
  white-space: nowrap;
  flex-shrink: 0;
}
.pt-label::after { content: ':'; margin-right: 2px; }
.pt-value {
  font-size: 9.5px;
  font-weight: 700;
  color: var(--text-main);
  word-break: break-word;
  min-width: 0;
}
.pt-value.doctor { color: var(--primary); }

/* ═══════════════════════════════════════════
   SECTION LABEL
═══════════════════════════════════════════ */
.section-label {
  font-size: 8px;
  font-weight: 800;
  letter-spacing: 1.2px;
  text-transform: uppercase;
  color: var(--text-muted);
  margin-bottom: 4px;
}

/* ═══════════════════════════════════════════
   ITEMS TABLE — FIXED LAYOUT (prevents overflow)
═══════════════════════════════════════════ */
.items-table {
  width: 100%;
  table-layout: fixed;        /* ← KEY: enforces column widths strictly */
  border-collapse: collapse;
  margin-bottom: 10px;
  border-top: 2px solid var(--border-mid);
  border-bottom: 2px solid var(--border-mid);
}
.items-table thead tr { background: #eef2f7; }
.items-table thead th {
  padding: 4px 2px;
  font-size: 7.5px;
  font-weight: 800;
  text-transform: uppercase;
  color: #334155;
  border-bottom: 1px solid var(--border-mid);
  overflow: visible;   /* do NOT clip — allows text to be fully visible */
  white-space: nowrap;
}
.items-table tbody td {
  padding: 3px 2px;
  font-size: 9px;
  border-bottom: 1px solid var(--border);
  overflow: visible;   /* do NOT clip — critical for TOTAL column numbers */
}

/* Column widths — must sum to exactly 100% to prevent squeeze */
.col-sl    { width: 3%;  text-align: center; }
.col-desc  { width: 23%; text-align: left;   }  /* More room for description */
.col-hsn   { width: 6%;  text-align: center; }
.col-batch { width: 8%;  text-align: center; }
.col-exp   { width: 5%;  text-align: center; }
.col-qty   { width: 4%;  text-align: center; }
.col-rate  { width: 7%;  text-align: right;  }
.col-disc  { width: 5%;  text-align: center; }
.col-cgp   { width: 5%;  text-align: center; }
.col-cga   { width: 7%;  text-align: right;  }
.col-sgp   { width: 5%;  text-align: center; }
.col-sga   { width: 7%;  text-align: right;  }
.col-tot   { width: 10%; text-align: right; padding-right: 4px !important; } /* Brought closer to SGST, right padding protects from edge */

/* Table cell helpers */
.cell-sl    { text-align: center; color: var(--text-muted); font-weight: 600; }
.cell-desc  { text-align: left;   font-weight: 700; color: var(--text-main); word-break: break-word; }
.cell-c     { text-align: center; font-family: 'Inter', monospace; color: var(--text-muted); }
.cell-r     { text-align: right;  font-family: 'Inter', monospace; }
.cell-total {
  text-align: right;
  font-weight: 800;
  color: var(--primary-dk);
  white-space: nowrap;         /* ← prevent total from wrapping */
  font-size: 9.5px;
}
.item-mfg {
  font-size: 7.5px;
  color: var(--text-muted);
  font-weight: 500;
  margin-top: 1px;
}
.bold  { font-weight: 700; }
.muted { color: var(--text-muted); }

/* ═══════════════════════════════════════════
   TOTALS BOX
═══════════════════════════════════════════ */
.totals-wrap {
  display: flex;
  justify-content: flex-end;
  margin-bottom: 10px;
}
.totals-box { width: 240px; }
.tot-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 3px 6px;
  font-size: 9.5px;
  border-bottom: 1px dashed var(--border);
}
.tot-row:last-child { border-bottom: none; }
.tot-row .lbl { font-weight: 600; color: var(--text-muted); }
.tot-row .val { font-weight: 700; color: var(--text-main);  font-family: 'Inter', monospace; white-space: nowrap; }
.tot-row.grand {
  font-size: 11px;
  font-weight: 900;
  color: var(--primary);
  border-top: 2px solid var(--primary);
  border-bottom: 3px double var(--primary);
  padding: 4px 6px;
  background: #f0faf4;
}
.tot-row.grand .lbl { color: var(--primary); }
.tot-row.grand .val { color: var(--primary); font-size: 12px; }
.tot-row.paid  .val { color: var(--primary); }

/* ═══════════════════════════════════════════
   AMOUNT IN WORDS
═══════════════════════════════════════════ */
.amount-words {
  display: flex;
  align-items: center;
  gap: 8px;
  background: var(--bg-light);
  border: 1px solid var(--border);
  border-radius: 6px;
  padding: 6px 12px;
  margin-bottom: 10px;
  font-size: 9.5px;
  font-weight: 600;
}
.amount-words .aw-lbl {
  font-size: 7.5px;
  text-transform: uppercase;
  font-weight: 800;
  color: var(--text-muted);
  background: var(--border);
  padding: 2px 6px;
  border-radius: 4px;
  white-space: nowrap;
  letter-spacing: 0.5px;
}

/* ═══════════════════════════════════════════
   BOTTOM META (Receipt / Date / Pharmacist)
═══════════════════════════════════════════ */
.bottom-meta {
  display: flex;
  justify-content: space-between;
  align-items: center;
  background: var(--bg-light);
  border: 1px solid var(--border);
  border-radius: 8px;
  padding: 6px 12px;
  margin-bottom: 8px;
}
.meta-item { display: flex; flex-direction: row; align-items: baseline; gap: 4px; }
.meta-lbl  { font-size: 8px; font-weight: 800; text-transform: uppercase; color: var(--text-muted); letter-spacing: 0.5px; }
.meta-val  { font-size: 9.5px; font-weight: 700; color: var(--text-main); }

/* ═══════════════════════════════════════════
   POLICY NOTE
═══════════════════════════════════════════ */
.policy-note {
  display: flex;
  align-items: center;
  gap: 6px;
  background: #fff7f7;
  border: 1px dashed #fca5a5;
  border-radius: 6px;
  padding: 6px 10px;
  font-size: 9px;
  color: #991b1b;
  font-weight: 600;
  margin-bottom: 12px;
}

/* ═══════════════════════════════════════════
   FOOTER
═══════════════════════════════════════════ */
.footer {
  text-align: center;
  border-top: 2px dashed var(--border);
  padding-top: 10px;
  font-size: 10px;
  font-weight: 600;
  color: var(--text-muted);
}

/* ═══════════════════════════════════════════
   A5 MODE (screen preview)
═══════════════════════════════════════════ */
body.is-a5 .invoice-container { font-size: 7.5px; }
body.is-a5 .hdr-name          { font-size: 12px; }
body.is-a5 .hdr-sub, body.is-a5 .hdr-addr, body.is-a5 .hdr-dl { font-size: 7px; }
body.is-a5 .hdr-gst           { font-size: 7.5px; }
body.is-a5 .invoice-title     { font-size: 8px; padding: 1px 8px; margin-top: 3px; }
body.is-a5 .hdr               { padding-bottom: 5px; margin-bottom: 5px; }
body.is-a5 .pt-grid           { padding: 4px 8px; gap: 2px 8px; margin-bottom: 4px; }
body.is-a5 .pt-label          { font-size: 6.5px; }
body.is-a5 .pt-value          { font-size: 8px; }
body.is-a5 .section-label     { font-size: 6.5px; margin-bottom: 2px; }
body.is-a5 .items-table thead th { font-size: 6px; padding: 2px 1px; }
body.is-a5 .items-table tbody td { font-size: 7px; padding: 2px 1px; }
body.is-a5 .item-mfg          { font-size: 5.5px; margin-top: 0; }
body.is-a5 .cell-total        { font-size: 7.5px; }
body.is-a5 .totals-box        { width: 175px; }
body.is-a5 .tot-row           { font-size: 7.5px; padding: 2px 4px; }
body.is-a5 .tot-row.grand     { font-size: 9px; }
body.is-a5 .amount-words      { font-size: 7.5px; padding: 4px 8px; margin-bottom: 6px; }
body.is-a5 .bottom-meta       { padding: 5px 8px; gap: 5px; margin-bottom: 6px; }
body.is-a5 .meta-val          { font-size: 9px; }
body.is-a5 .meta-lbl          { font-size: 6.5px; }
body.is-a5 .policy-note       { font-size: 7px; padding: 4px 8px; margin-bottom: 6px; }
body.is-a5 .footer            { font-size: 7.5px; padding-top: 6px; }

/* A5 PRINT — aggressive compression to fit A5 page without overflow */
@media print {
  body.is-a5 {
    font-size: 7px !important;
  }
  body.is-a5 .hdr            { padding-bottom: 2px; margin-bottom: 2px; }
  body.is-a5 .hdr-name       { font-size: 11px !important; color: #000 !important; font-weight: 900 !important; }
  body.is-a5 .hdr-sub,
  body.is-a5 .hdr-addr,
  body.is-a5 .hdr-dl         { font-size: 6.5px !important; color: #000 !important; }
  body.is-a5 .hdr-gst        { font-size: 7px !important; color: #000 !important; }
  body.is-a5 .invoice-title  { font-size: 7.5px !important; padding: 1px 6px; margin-top: 2px; }
  body.is-a5 .pt-grid        { padding: 2px 4px; gap: 1px 4px; margin-bottom: 2px; }
  body.is-a5 .pt-label       { font-size: 6px !important; }
  body.is-a5 .pt-value       { font-size: 7.5px !important; color: #000 !important; }
  body.is-a5 .section-label  { font-size: 6px !important; margin-bottom: 1px; }
  body.is-a5 .items-table    { margin-bottom: 3px; }
  body.is-a5 .items-table thead th { font-size: 5.5px !important; padding: 1px 1px; color: #000 !important; }
  body.is-a5 .items-table tbody td { font-size: 6.5px !important; padding: 1px 1px; color: #000 !important; }
  body.is-a5 .item-mfg       { font-size: 5px !important; }
  body.is-a5 .cell-total     { font-size: 7px !important; white-space: nowrap; color: #000 !important; }
  body.is-a5 .totals-wrap    { margin-bottom: 3px; }
  body.is-a5 .totals-box     { width: 160px; }
  body.is-a5 .tot-row        { font-size: 7px !important; padding: 1px 3px; color: #000 !important; }
  body.is-a5 .tot-row.grand  { font-size: 8.5px !important; color: #000 !important; }
  body.is-a5 .tot-row.grand .lbl,
  body.is-a5 .tot-row.grand .val { color: #000 !important; }
  body.is-a5 .amount-words   { font-size: 7px !important; padding: 2px 4px; margin-bottom: 3px; color: #000 !important; }
  body.is-a5 .bottom-meta    { padding: 2px 4px; gap: 4px; margin-bottom: 2px; }
  body.is-a5 .meta-val       { font-size: 7.5px !important; color: #000 !important; }
  body.is-a5 .meta-lbl       { font-size: 7px !important; }
  body.is-a5 .policy-note    { font-size: 6.5px !important; padding: 2px 4px; margin-bottom: 2px; }
  body.is-a5 .footer         { font-size: 7px !important; padding-top: 3px; color: #000 !important; }
  /* Column widths: tighten Description, widen Total on A5 */
  body.is-a5 .col-desc  { width: 23% !important; }
  body.is-a5 .col-tot   { width: 10% !important; padding-right: 4px !important; }
  body.is-a5 .col-batch { width: 7%; }
}

/* ═══════════════════════════════════════════
   RECEIPT MODE (80mm thermal / small receipt paper)
   Items shown as stacked list instead of wide table
═══════════════════════════════════════════ */
body.is-receipt {
  font-size: 8.5px;
}
body.is-receipt .invoice-wrapper { max-width: 340px; }
body.is-receipt .hdr             { padding-bottom: 4px; margin-bottom: 4px; }
body.is-receipt .hdr-name        { font-size: 11px; letter-spacing: 0.5px; }
body.is-receipt .hdr-sub,
body.is-receipt .hdr-addr,
body.is-receipt .hdr-dl          { font-size: 7px; }
body.is-receipt .hdr-gst         { font-size: 7.5px; }
body.is-receipt .invoice-title   { font-size: 8px; }
body.is-receipt .pt-grid         { grid-template-columns: 1fr 1fr; padding: 4px 6px; gap: 2px 6px; margin-bottom: 4px; }
body.is-receipt .pt-label        { font-size: 6.5px; }
body.is-receipt .pt-value        { font-size: 8px; }
/* Hide the wide table in receipt mode — show compact list instead */
body.is-receipt .items-table     { display: none; }
body.is-receipt .receipt-list    { display: block !important; }
body.is-receipt .totals-box      { width: 100%; }
body.is-receipt .totals-wrap     { justify-content: stretch; }
body.is-receipt .tot-row         { font-size: 8.5px; padding: 2px 0; }
body.is-receipt .tot-row.grand   { font-size: 10.5px; }
body.is-receipt .amount-words    { font-size: 8px; }
body.is-receipt .bottom-meta     { grid-template-columns: 1fr 1fr; }
body.is-receipt .policy-note     { font-size: 7.5px; }
body.is-receipt .footer          { font-size: 8px; }
/* Receipt print @page */
@media print {
  body.is-receipt .action-bar  { display: none !important; }
  body.is-receipt .watermark   { font-size: 60px !important; }
}
</style>
</head>
<body>

<div class="invoice-wrapper">

  <!-- ── Receipt compact item list (shown only in receipt/80mm mode) ── -->
  <div class="receipt-list" style="display:none; margin-bottom:8px;">
    <div style="font-size:7px;font-weight:800;text-transform:uppercase;color:#64748b;letter-spacing:1px;border-bottom:1px dashed #e2e8f0;padding-bottom:3px;margin-bottom:4px;">
      Item Details
    </div>
    {$receiptRows}
    <div style="border-top:1px dashed #e2e8f0;margin-top:3px;"></div>
  </div>

  <!-- ── Action bar (hidden on print) ── -->
  <div class="action-bar">
    <span class="size-label" id="size-indicator">&#x1F4C4; A4 Portrait</span>
    <button class="btn btn-close" onclick="window.close()">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M18 6 6 18"/><path d="m6 6 12 12"/></svg>
      Close
    </button>
    <button class="btn btn-a5" id="toggle-size-btn" onclick="togglePageSize()">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><path d="M3 9h18"/></svg>
      Toggle Size
    </button>
    <button class="btn btn-print" onclick="window.print()">
      <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="6 9 6 2 18 2 18 9"/><path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/><rect x="6" y="14" width="12" height="8"/></svg>
      Print Invoice
    </button>
  </div>

  <!-- ── Invoice card ── -->
  <div class="invoice-container">

    <div class="watermark">GM HOSPITAL</div>

    <!-- ── Header ── -->
    <div class="hdr">
      <div class="hdr-name">BASAVESHWAR NAGAR PHARMA</div>
      <div class="hdr-sub">(A unit of pan NAGARABHAVI Hospitalals pvt ltd)</div>
      <div class="hdr-addr">No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road, Basaveshwara nagar, Bengaluru 560079</div>
      <div class="hdr-dl">D.L. No. KA20-B04-103613 &nbsp;/&nbsp; KA21-B04-103614</div>
      <div class="hdr-gst">GSTIN: 29AAFCP8756N3ZE</div>
    </div>

    <!-- ── Patient / Invoice Info ── -->
    <div class="pt-grid">
      <div class="pt-field">
        <span class="pt-label">Patient Name</span>
        <span class="pt-value">{$cName}</span>
      </div>
      <div class="pt-field">
        <span class="pt-label">Invoice No</span>
        <span class="pt-value">{$inv}</span>
      </div>
      <div class="pt-field">
        <span class="pt-label">Date &amp; Time</span>
        <span class="pt-value">{$invDate} &ndash; {$invTimeFormatted}</span>
      </div>
      <div class="pt-field">
        <span class="pt-label">Phone</span>
        <span class="pt-value">{$cPhone}</span>
      </div>
      <div class="pt-field">
        <span class="pt-label">Payment Mode</span>
        <span class="pt-value">{$paymentMethod}</span>
      </div>
      <div class="pt-field">
        <span class="pt-label">Doctor</span>
        <span class="pt-value doctor">{$doctorName}</span>
      </div>
    </div>

    <!-- ── Items Table ── -->
    <div class="section-label">Item Details</div>
    <table class="items-table">
      <thead>
        <tr>
          <th class="col-sl">SL</th>
          <th class="col-desc" style="text-align:left">Description</th>
          <th class="col-hsn">HSN</th>
          <th class="col-batch">Batch</th>
          <th class="col-exp">Expiry</th>
          <th class="col-qty">Qty</th>
          <th class="col-rate">Rate</th>
          <th class="col-disc">Disc%</th>
          <th class="col-cgp">CGST%</th>
          <th class="col-cga">CGST({$c})</th>
          <th class="col-sgp">SGST%</th>
          <th class="col-sga">SGST({$c})</th>
          <th class="col-tot">Total ({$c})</th>
        </tr>
      </thead>
      <tbody>{$rows}</tbody>
    </table>

    <!-- ── Totals ── -->
    <div class="totals-wrap">
      <div class="totals-box">
        <div class="tot-row">
          <span class="lbl">Sub Total</span>
          <span class="val">{$c} &#x200B;{$fSub}</span>
        </div>
        <div class="tot-row" style="color:#dc2626">
          <span class="lbl" style="color:#dc2626">Discount</span>
          <span class="val" style="color:#dc2626">&#x2212; {$c} {$fDisc}</span>
        </div>
        <div class="tot-row grand" style="border-bottom:none;">
          <span class="lbl">Net Payable</span>
          <span class="val">{$c} {$fGrand}</span>
        </div>
      </div>
    </div>

    <!-- ── Amount in Words ── -->
    <div class="amount-words">
      <span class="aw-lbl">Amount in Words</span>
      {$wrds}
    </div>

    <!-- ── Receipt / Date / Pharmacist ── -->
    <div class="bottom-meta">
      <div class="meta-item">
        <span class="meta-lbl">Receipt No:</span>
        <span class="meta-val">{$inv}</span>
      </div>
      <div class="meta-item">
        <span class="meta-lbl">Date &amp; Time:</span>
        <span class="meta-val">{$invDate}, {$invTimeFormatted}</span>
      </div>
      <div class="meta-item">
        <span class="meta-lbl">Pharmacist:</span>
        <span class="meta-val">{$printedBy}</span>
      </div>
    </div>

    <!-- ── Policy Note ── -->
    <div class="policy-note">
      <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink:0"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
      <span><strong>Note:</strong> Items eligible for return within 15 days of purchase with original receipt. Medicines once sold will not be returned without valid prescription.</span>
    </div>

    <!-- ── Footer ── -->
    <div class="footer">
      
    </div>

  </div><!-- /.invoice-container -->
</div><!-- /.invoice-wrapper -->

<script>
// ── Page-size toggle: A4 → A5 Portrait → A5 Landscape → Receipt (80mm) → A4
var currentSize = 'a4';
var pageStyleEl = document.getElementById('page-size-style');
var sizeLabel   = document.getElementById('size-indicator');

var sizeConfigs = [
  {
    id: 'a4',
    // No 'size' override for A4 — let the printer use its loaded paper
    // This prevents the "paper doesn't match" mismatch dialog
    label: '\u{1F4C4} A4 Portrait',
    page: '@page { margin: 10mm; }',
    bodyClass: ''
  },
  {
    id: 'a5-portrait',
    label: '\u{1F4C4} A5 Portrait',
    page: '@page { size: A5 portrait; margin: 5mm; }',
    bodyClass: 'is-a5'
  },
  {
    id: 'a5-landscape',
    label: '\u{1F4C4} A5 Landscape',
    page: '@page { size: A5 landscape; margin: 5mm; }',
    bodyClass: 'is-a5'
  },
  {
    id: 'receipt',
    label: '\u{1F9FE} Receipt (80mm)',
    page: '@page { size: 80mm auto; margin: 3mm 2mm; }',
    bodyClass: 'is-receipt'
  }
];

var sizeIdx = 0; // start at A4

function togglePageSize() {
  // Remove all size classes from body
  document.body.classList.remove('is-a5', 'is-receipt');

  // Move to next size
  sizeIdx = (sizeIdx + 1) % sizeConfigs.length;
  var cfg = sizeConfigs[sizeIdx];

  // Apply @page style
  pageStyleEl.textContent = cfg.page;

  // Apply body class
  if (cfg.bodyClass) {
    document.body.classList.add(cfg.bodyClass);
  }

  // Update label
  sizeLabel.textContent = cfg.label;
  currentSize = cfg.id;
}

// Auto-print after delay (gives Google Fonts time to load)
setTimeout(function() { window.print(); }, 1200);
</script>
</body>
</html>
HTML;
    }

    /**
     * Convert a number to English words (Indian numbering system)
     */
    public function numToWords(float $n): string {
        $n = (int)round($n);
        if ($n <= 0) return 'Zero';
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve',
                 'Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if ($n < 20)       return $ones[$n];
        if ($n < 100)      return $tens[(int)($n / 10)] . ($n % 10 ? ' ' . $ones[$n % 10] : '');
        if ($n < 1000)     return $ones[(int)($n / 100)] . ' Hundred' . ($n % 100 ? ' ' . $this->numToWords($n % 100) : '');
        if ($n < 100000)   return $this->numToWords((int)($n / 1000))   . ' Thousand' . ($n % 1000   ? ' ' . $this->numToWords($n % 1000)   : '');
        if ($n < 10000000) return $this->numToWords((int)($n / 100000)) . ' Lakh'     . ($n % 100000 ? ' ' . $this->numToWords($n % 100000) : '');
        return $this->numToWords((int)($n / 10000000)) . ' Crore' . ($n % 10000000 ? ' ' . $this->numToWords($n % 10000000) : '');
    }
}
