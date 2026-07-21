<?php
namespace GM_HMS\Modules\Pharmacy\Services;

/**
 * InvoiceRenderer
 * Handles generating HTML for pharmacy invoices
 */
class InvoiceRenderer {
    
    /**
     * Build invoice HTML for printing
     */
    public function render(array $m, array $items, string $printedBy = 'Pharmacist'): string {
        $c    = '₹';
        $inv  = $m['invoice_no'];
        $cName = $m['customer_name'] ?? 'Walk-in';
        $sub  = (float)$m['subtotal'];
        $disc = (float)$m['discount_amount'];
        $tax  = (float)$m['tax_total'];
        $grand = (float)$m['grand_total'];
        $paid = (float)$m['paid_amount'];
        $bal  = (float)$m['balance'];
        
        $doctorName = 'Self / Walk-in';
        if (!empty($m['customer_id'])) {
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
                    $dName = trim($docRow['doctor_name']);
                    $doctorName = (stripos($dName, 'Dr.') === 0 || stripos($dName, 'Dr ') === 0) ? $dName : 'Dr. ' . $dName;
                } else {
                    $prescRow = $db->fetchOne(
                        "SELECT COALESCE(d.full_name, p.doctor_id) AS doctor_name 
                         FROM prescriptions p 
                         LEFT JOIN doctors d ON d.doctor_id = p.doctor_id 
                         WHERE p.patient_id = ? 
                         ORDER BY p.prescription_date DESC 
                         LIMIT 1",
                        [$m['customer_id']]
                    );
                    if ($prescRow && !empty($prescRow['doctor_name'])) {
                        $dName = trim($prescRow['doctor_name']);
                        $doctorName = (stripos($dName, 'Dr.') === 0 || stripos($dName, 'Dr ') === 0) ? $dName : 'Dr. ' . $dName;
                    }
                }
            } catch (\Exception $e) {
                // Keep default
            }
        }
        
        $t    = date('d M Y, h:i A');
        $bLbl = $bal < 0 ? 'Balance Due' : 'Change';
        $bClr = $bal < 0 ? '#e53e3e'    : '#1f6b4a';
        $wrds = $this->numToWords((float)$grand) . ' Rupees Only';

        $rows = '';
        foreach ($items as $i => $item) {
            $rate = (float)$item['rate'];
            $qty  = (int)$item['qty'];
            $dp   = (float)($item['discount_percent'] ?? 0);
            $da   = round($qty * $rate * $dp / 100, 2);
            $dc   = $da > 0 ? "-{$c}" . number_format($da, 2) : '—';
            
            // GST split calculations
            $gst_pct = (float)($item['tax_percent'] ?? 12);
            $gst_amt = (float)($item['tax_amount'] ?? 0);
            
            $cgst_pct = $gst_pct / 2;
            $sgst_pct = $gst_pct / 2;
            $cgst_amt = $gst_amt / 2;
            $sgst_amt = $gst_amt / 2;
            
            // Format expiry date cleanly like MM/YY
            $expStr = '—';
            if (!empty($item['expiry_date']) && $item['expiry_date'] !== '0000-00-00') {
                $expStr = date('m/y', strtotime($item['expiry_date']));
            }
            
            $bg   = $i % 2 ? '#f8fafc' : '#fff';
            $rows .= "<tr style='background:{$bg}'>
              <td style='text-align:center;padding:3px 2px;color:var(--text-muted);font-weight:500;border-bottom:1px solid #e2e8f0;'>".($i+1)."</td>
              <td style='padding:3px 2px;font-weight:700;color:var(--text-main);font-size:10px;border-bottom:1px solid #e2e8f0;'>
                " . htmlspecialchars($item['product_name']) . "
                " . (!empty($item['manufacturer']) ? "<div style='font-size:7.5px;color:var(--text-muted);font-weight:500;margin-top:0;'>Mfg: " . htmlspecialchars($item['manufacturer']) . "</div>" : "") . "
              </td>
              <td style='text-align:center;padding:3px 2px;font-family:monospace;font-size:9px;color:var(--text-muted);font-weight:500;border-bottom:1px solid #e2e8f0;'>" . htmlspecialchars($item['hsn_code'] ?: '—') . "</td>
              <td style='text-align:center;padding:3px 2px;font-family:monospace;font-size:9px;color:var(--text-muted);font-weight:500;border-bottom:1px solid #e2e8f0;'>" . htmlspecialchars($item['batch_no'] ?: '—') . "</td>
              <td style='text-align:center;padding:3px 2px;font-family:monospace;font-size:9px;color:var(--text-muted);font-weight:500;border-bottom:1px solid #e2e8f0;'>{$expStr}</td>
              <td style='text-align:center;padding:3px 2px;font-weight:700;font-size:10.5px;border-bottom:1px solid #e2e8f0;'>{$qty}</td>
              <td style='text-align:right;padding:3px 2px;font-weight:600;border-bottom:1px solid #e2e8f0;'>".number_format($rate,2)."</td>
              <td style='text-align:center;padding:3px 2px;color:var(--text-muted);font-size:9px;border-bottom:1px solid #e2e8f0;'>".number_format($cgst_pct,1)."%</td>
              <td style='text-align:right;padding:3px 2px;color:var(--text-muted);font-size:9.5px;border-bottom:1px solid #e2e8f0;'>".number_format($cgst_amt,2)."</td>
              <td style='text-align:center;padding:3px 2px;color:var(--text-muted);font-size:9px;border-bottom:1px solid #e2e8f0;'>".number_format($sgst_pct,1)."%</td>
              <td style='text-align:right;padding:3px 2px;color:var(--text-muted);font-size:9.5px;border-bottom:1px solid #e2e8f0;'>".number_format($sgst_amt,2)."</td>
              <td style='text-align:right;padding:3px 2px;font-weight:800;color:var(--primary-dark);font-size:10.5px;border-bottom:1px solid #e2e8f0;'>".number_format((float)($item['total'] ?? $item['subtotal'] ?? 0),2)."</td>
            </tr>";
        }

        return "<!DOCTYPE html><html><head>
    <link rel='stylesheet' href='/GM_HMS/assets/css/gm-theme.css'>
<meta charset='UTF-8'><title>Invoice {$inv}</title>
<link href='https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap' rel='stylesheet'>
<style>
:root { --primary: #1f6b4a; --primary-dark: #096b6b; --text-main: #1e293b; --text-muted: #64748b; --bg-light: #f8fafc; --border: #e2e8f0; }
*{box-sizing:border-box;margin:0;padding:0}
body{font-family:'Inter',sans-serif;color:var(--text-main);font-size:10px;line-height:1.4}
@media screen {
  body { background: #e2e8f0; padding: 40px 20px; display: flex; flex-direction: column; align-items: center; }
  .invoice-wrapper { width: 100%; max-width: 850px; }
  .action-bar { display: flex; justify-content: flex-end; margin-bottom: 20px; gap: 12px; }
  .btn { padding: 8px 16px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 12px; display: flex; align-items: center; gap: 8px; transition: all 0.2s ease; font-family: 'Inter', sans-serif; }
  .btn-print { background: var(--primary); color: #fff; box-shadow: 0 4px 12px rgba(31, 107, 74,0.25); }
  .btn-print:hover { background: var(--primary-dark); transform: translateY(-1px); box-shadow: 0 6px 15px rgba(31, 107, 74,0.3); }
  .btn-a5 { background: #334155; color: #fff; }
  .btn-close { background: #fff; color: var(--text-muted); border: 1px solid var(--border); }
  .btn-close:hover { background: #f1f5f9; color: var(--text-main); }
  .invoice-container { background: #fff; padding: 24px; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.08); }
}
@media print {
  body { background: #fff; padding: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
  .action-bar { display: none !important; }
  .invoice-container { padding: 0; box-shadow: none; border-radius: 0; max-width: 100%; position: relative; z-index: 1; }
  @page { margin: 5mm; size: A4 portrait; }
}
body.is-a5 @media print {
  @page { size: A5 landscape; }
  .invoice-container { font-size: 9px; }
}

.watermark {
  position: fixed;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%) rotate(-35deg);
  font-size: 100px;
  font-weight: 900;
  color: rgba(31, 107, 74, 0.08);
  z-index: 9999;
  pointer-events: none;
  white-space: nowrap;
  user-select: none;
}
@media print {
  .watermark { color: rgba(31, 107, 74, 0.15) !important; }
}

.hdr{text-align:center;padding-bottom:4px;border-bottom:1px dashed var(--border);margin-bottom:6px}
.cn{font-size:16px;font-weight:800;color:var(--primary);text-transform:uppercase;margin-bottom:1px;letter-spacing:0.5px}
.hdr-sub{font-size:9px;font-weight:700;color:var(--text-main);margin-bottom:1px}
.hdr-addr{font-size:9px;color:var(--text-muted);margin-bottom:1px}
.hdr-dl{font-size:9px;color:var(--text-muted);font-weight:600;margin-bottom:1px}
.hdr-gst{font-size:9.5px;color:var(--text-main);font-weight:700;display:inline-block;background:var(--bg-light);padding:1px 6px;border-radius:20px;border:1px solid var(--border);margin-top:1px}

.pt{margin-bottom:6px;display:grid;grid-template-columns:repeat(3, 1fr);gap:2px 10px;padding:2px 0;}
.pf{display:flex;flex-direction:row;align-items:center;gap:4px}
.pl{font-size:9px;font-weight:700;color:var(--text-muted);text-transform:uppercase;}
.pl::after{content:':'}
.pv{font-size:10.5px;font-weight:600;color:var(--text-main)}

.st{font-size:9px;font-weight:800;letter-spacing:1px;text-transform:uppercase;color:var(--text-muted);margin-bottom:4px}
table{width:100%;border-collapse:collapse;margin-bottom:8px;border-top:1px solid #cbd5e1;border-bottom:1px solid #cbd5e1;}
thead tr{background:#f1f5f9}
thead th{color:#1e293b;padding:4px 2px;font-size:8.5px;font-weight:800;text-transform:uppercase;text-align:left;border-bottom:1px solid #cbd5e1;}
tbody td{border:none;font-size:10px;padding:4px 2px}

.tw{display:flex;justify-content:flex-end;margin-bottom:8px}
.to{width:220px;}
.tr2{display:flex;justify-content:space-between;padding:3px 4px;font-size:10px;border-bottom:1px dashed var(--border)}
.tr2:last-child{border-bottom:none}
.tg{font-size:11.5px;font-weight:800;color:var(--primary);padding:4px;display:flex;justify-content:space-between;border-top:1px solid var(--primary);border-bottom:2px double var(--primary);}

.aw{background:var(--bg-light);border:1px solid var(--border);border-radius:6px;padding:6px 12px;font-size:11px;margin-bottom:12px;font-weight:500;display:flex;align-items:center;gap:8px}
.aw strong{font-size:9px;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.5px;background:#e2e8f0;padding:2px 6px;border-radius:4px}
.policy-note{background:#fdf2f2;border:1px dashed #f87171;border-radius:6px;padding:8px 12px;font-size:10.5px;color:#991b1b;margin-bottom:15px;font-weight:600;display:flex;align-items:center;gap:6px}
.bottom-meta-grid{display:grid;grid-template-columns:repeat(3, 1fr);gap:10px;background:var(--bg-light);border:1px solid var(--border);border-radius:8px;padding:8px 12px;margin-bottom:12px}
.bottom-meta-item{display:flex;flex-direction:column;gap:2px}
.bottom-meta-label{font-size:8px;font-weight:800;text-transform:uppercase;color:var(--text-muted);letter-spacing:0.5px}
.bottom-meta-value{font-size:11px;font-weight:600;color:var(--text-main)}

.ft{display:flex;justify-content:space-between;border-top:2px dashed var(--border);padding-top:16px}
.fn{font-size:12px;color:var(--text-muted);font-style:italic;font-weight:500}
.fp{font-size:11px;color:var(--text-muted);text-align:right;line-height:1.6}
.fp strong{color:var(--text-main);font-weight:700}
</style></head><body>

<div class='invoice-wrapper'>
  <div class='watermark'>GM HOSPITAL</div>
  <div class='action-bar'>
    <button class='btn btn-close' onclick='window.close()'>
      <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><path d='M18 6 6 18'/><path d='m6 6 12 12'/></svg> Close
    </button>
    <button class='btn btn-a5' onclick='document.body.classList.toggle(\"is-a5\")'>
      Toggle A5 Print
    </button>
    <button class='btn btn-print' onclick='window.print()'>
      <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 6 2 18 2 18 9'></polyline><path d='M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2'></path><rect x='6' y='14' width='12' height='8'></rect></svg> Print Invoice
    </button>
  </div>
  
  <div class='invoice-container'>
    <div class='hdr'>
      <div class='cn'>BASAVESHWAR NAGAR PHARMA</div>
      <div class='hdr-sub'>(A unit of pan NAGARABHAVI Hospitalals pvt ltd)</div>
      <div class='hdr-addr'>No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road, Basaveshwara nagar, Bengaluru 560079</div>
      <div class='hdr-dl'>D.L. No. KA20-B04-103613 / KA21-B04-103614</div>
      <div class='hdr-gst'>GSTIN: 29AAFCP8756N3ZE</div>
    </div>
    
    <div class='pt'>
      <div class='pf'><span class='pl'>Patient Name</span><span class='pv'>".htmlspecialchars($cName)."</span></div>
      <div class='pf'><span class='pl'>Invoice No</span><span class='pv'>".htmlspecialchars($inv)."</span></div>
      <div class='pf'><span class='pl'>Issue Date & Time</span><span class='pv'>".date('d M Y', strtotime($m['invoice_date'] ?? 'now'))." &ndash; ".($m['invoice_time'] ?? date('H:i'))."</span></div>
      <div class='pf'><span class='pl'>Phone Number</span><span class='pv'>".htmlspecialchars($m['customer_phone'] ?? '—')."</span></div>
      <div class='pf'><span class='pl'>Payment Mode</span><span class='pv' style='text-transform:uppercase'>".htmlspecialchars($m['payment_method'])."</span></div>
      <div class='pf'><span class='pl'>Doctor</span><span class='pv' style='color:var(--primary);'>".htmlspecialchars($doctorName)."</span></div>
    </div>
    
    <div class='st'>Item Details</div>
    <table>
      <thead><tr>
        <th style='width:5%;text-align:center'>SL NO</th>
        <th style='width:18%;text-align:left'>DESCRIPTION</th>
        <th style='width:9%;text-align:center'>HSN</th>
        <th style='width:9%;text-align:center'>BATCH</th>
        <th style='width:8%;text-align:center'>EXPIRY</th>
        <th style='width:5%;text-align:center'>QTY</th>
        <th style='width:8%;text-align:right'>RATE</th>
        <th style='width:6%;text-align:center'>DISC %</th>
        <th style='width:6%;text-align:center'>CGST %</th>
        <th style='width:8%;text-align:right'>CGST (₹)</th>
        <th style='width:6%;text-align:center'>SGST %</th>
        <th style='width:8%;text-align:right'>SGST (₹)</th>
        <th style='width:10%;text-align:right'>TOTAL (₹)</th>
      </tr></thead>
      <tbody>{$rows}</tbody>
    </table>
    
    <div class='tw'>
      <div class='to'>
        <div class='tr2'><span>Total</span><span style='font-weight:600'>{$c}".number_format($sub,2)."</span></div>
        <div class='tr2'><span>Discount</span><span style='color:#e53e3e;font-weight:600'>-{$c}".number_format($disc,2)."</span></div>
        <div class='tg'><span>Net Payable</span><span>{$c}".number_format($grand,2)."</span></div>
        <div class='tr2' style='color:var(--primary);font-weight:700'><span>Amount Paid</span><span>{$c}".number_format($paid,2)."</span></div>
        <div class='tr2' style='color:{$bClr};font-weight:700'><span>{$bLbl}</span><span>{$c}".number_format(abs($bal),2)."</span></div>
      </div>
    </div>
    
    <div class='aw'><strong>Amount in Words</strong> ".htmlspecialchars($wrds)."</div>
    
    <div class='bottom-meta-grid'>
      <div class='bottom-meta-item'>
        <span class='bottom-meta-label'>Receipt No</span>
        <span class='bottom-meta-value'>".htmlspecialchars($inv)."</span>
      </div>
      <div class='bottom-meta-item'>
        <span class='bottom-meta-label'>Date &amp; Time</span>
        <span class='bottom-meta-value'>".date('d M Y, h:i A')."</span>
      </div>
      <div class='bottom-meta-item'>
        <span class='bottom-meta-label'>pharmacist</span>
        <span class='bottom-meta-value'>".htmlspecialchars($printedBy)."</span>
      </div>
    </div>
    
    <div class='policy-note' style='margin-top: 15px; margin-bottom: 15px;'>
      <svg width='14' height='14' viewBox='0 0 24 24' fill='none' stroke='currentColor' stroke-width='2.5' stroke-linecap='round' stroke-linejoin='round' style='margin-right:4px;flex-shrink:0;'><circle cx='12' cy='12' r='10'/><line x1='12' y1='16' x2='12' y2='12'/><line x1='12' y1='8' x2='12.01' y2='8'/></svg>
      <span><strong>Note:</strong> Items eligible for return within 15 days of purchase with original receipt.</span>
    </div>
    
    <div class='ft' style='border-top: 2px dashed var(--border); padding-top: 12px; display: flex; justify-content: center; width: 100%;'>
      <div class='fn' style='text-align:center; font-weight:700; color:var(--text-muted); font-size:11px; font-style:normal;'>
        Get well soon! &bull; Thank you for choosing BASAVESHWAR NAGAR PHARMA.
      </div>
    </div>
  </div>
</div>
<script>
setTimeout(() => { window.print(); }, 800);
</script>
</body></html>";
    }

    public function numToWords(float $n): string {
        $n = (int)round($n);
        if ($n <= 0) return 'Zero';
        $ones = ['','One','Two','Three','Four','Five','Six','Seven','Eight','Nine','Ten','Eleven','Twelve',
                 'Thirteen','Fourteen','Fifteen','Sixteen','Seventeen','Eighteen','Nineteen'];
        $tens = ['','','Twenty','Thirty','Forty','Fifty','Sixty','Seventy','Eighty','Ninety'];
        if ($n < 20)       return $ones[$n];
        if ($n < 100)      return $tens[(int)($n/10)] . ($n%10 ? ' '.$ones[$n%10] : '');
        if ($n < 1000)     return $ones[(int)($n/100)] . ' Hundred' . ($n%100 ? ' '.$this->numToWords($n%100) : '');
        if ($n < 100000)   return $this->numToWords((int)($n/1000)) . ' Thousand' . ($n%1000 ? ' '.$this->numToWords($n%1000) : '');
        if ($n < 10000000) return $this->numToWords((int)($n/100000)) . ' Lakh' . ($n%100000 ? ' '.$this->numToWords($n%100000) : '');
        return $this->numToWords((int)($n/10000000)) . ' Crore' . ($n%10000000 ? ' '.$this->numToWords($n%10000000) : '');
    }
}
