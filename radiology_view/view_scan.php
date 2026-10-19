<?php
/**
 * Radiology Scan Report Viewer
 * Displays Somatiq / uploaded scan PDF with the official GM HOSPITALS Letterhead Header.
 * Provides instant Print and PDF Download options.
 * Built with pure PHP & JavaScript (zero Python).
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GM_HMS/login.php');
    exit;
}

require_once __DIR__ . '/../models/Database.php';

$orderId = trim($_GET['order_id'] ?? $_POST['order_id'] ?? '');
$source  = strtoupper(trim($_GET['source'] ?? 'OPD'));
$from    = strtolower(trim($_GET['from'] ?? 'kanban'));

if (!$orderId) {
    die('Error: No Order ID provided.');
}

$db = new \Database();
$db->connect();

// Safe back URL
$fallbackBackUrl = 'kanban.php?source=' . ($source === 'IPD' ? 'ipd' : 'opd');

// Fetch order & radiology results
$targetTable = ($source === 'IPD') ? 'ipd_radiology_results' : 'radiology_results';

// Check if table exists, fallback if necessary
try {
    $tCheck = $db->fetchAll("SHOW TABLES LIKE '{$targetTable}'");
    if (empty($tCheck)) {
        $targetTable = ($source === 'IPD') ? 'ipd_lab_results' : 'lab_results';
    }
} catch (\Exception $e) {}

$row = $db->fetchOne("
    SELECT r.*, 
           CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
           p.phone AS patient_phone,
           p.sex, p.age
    FROM {$targetTable} r
    LEFT JOIN patient p ON r.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
    WHERE r.order_id = ?
    LIMIT 1
", [$orderId]);

// Fallback to opd_billing_master / patient if not found in results
if (!$row) {
    $row = $db->fetchOne("
        SELECT obm.bill_id AS order_id, obm.patient_id, obm.created_at AS result_date,
               CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
               p.phone AS patient_phone, p.sex, p.age,
               obi.item_name AS test_name
        FROM opd_billing_master obm
        LEFT JOIN patient p ON obm.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
        LEFT JOIN opd_billing_items obi ON obm.bill_id = obi.bill_id
        WHERE obm.bill_id = ?
        LIMIT 1
    ", [$orderId]);
}

$patientName = $row['patient_name'] ?? 'Patient';
$patientId   = $row['patient_id'] ?? '—';
$genderAge   = trim(($row['sex'] ?? '—') . ' / ' . ($row['age'] ?? '—') . ' Yrs');
$phone       = $row['patient_phone'] ?? '—';
$testName    = $row['test_name'] ?? 'Radiology Examination';
$modality    = $row['modality'] ?? 'RADIOLOGY';
$scanDate    = !empty($row['result_date']) ? date('d M Y', strtotime($row['result_date'])) : date('d M Y');
$scanTime    = !empty($row['result_time']) ? substr($row['result_time'], 0, 5) : '';
$reportFile  = $row['report_file'] ?? '';

// Check if report_url exists in result_data JSON
if (empty($reportFile) && !empty($row['result_data'])) {
    $rd = is_array($row['result_data']) ? $row['result_data'] : @json_decode($row['result_data'], true);
    if (!empty($rd['report_url'])) {
        $reportFile = $rd['report_url'];
    }
}

// Fetch PDF bytes cleanly via PHP to bypass CORS and X-Frame-Options: DENY
$pdfBase64 = '';
$hasPdf = false;

if (!empty($reportFile)) {
    if (strpos($reportFile, 'http://') === 0 || strpos($reportFile, 'https://') === 0) {
        $ctx = stream_context_create([
            'http' => [
                'follow_location' => 1,
                'timeout' => 12,
                'header' => "User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64)\r\n"
            ],
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);
        $pdfBytes = @file_get_contents($reportFile, false, $ctx);
        if ($pdfBytes && strpos($pdfBytes, '%PDF') !== false) {
            $pdfBase64 = base64_encode($pdfBytes);
            $hasPdf = true;
        }
    } else {
        $localPath = __DIR__ . '/../' . ltrim($reportFile, '/');
        if (file_exists($localPath)) {
            $pdfBytes = @file_get_contents($localPath);
            if ($pdfBytes && strpos($pdfBytes, '%PDF') !== false) {
                $pdfBase64 = base64_encode($pdfBytes);
                $hasPdf = true;
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Scan Report - <?= htmlspecialchars($orderId) ?> - GM Hospitals</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- PDF.js from Mozilla (Pure JavaScript PDF renderer) -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script>
        pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
    </script>
    
    <!-- html2pdf.js for client-side PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>

    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; color: #111; font-size: 11px; line-height: 1.35; background: #e2e8f0; }

        @media screen {
            body { padding: 30px 15px; display: flex; flex-direction: column; align-items: center; }
            .action-bar { display: flex; justify-content: flex-end; margin-bottom: 16px; gap: 10px; width: 100%; max-width: 860px; flex-wrap: wrap; }
            .btn { padding: 8px 18px; border-radius: 8px; font-weight: 700; cursor: pointer; border: none; font-size: 12px; display: inline-flex; align-items: center; justify-content: center; text-decoration: none; transition: all 0.15s ease; box-shadow: 0 2px 6px rgba(0,0,0,0.08); }
            .btn:hover { transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,0.12); }
            .btn-print { background: #1f6b4a; color: #fff; }
            .btn-print:hover { background: #18553a; }
            .btn-pdf { background: #e11d48; color: #fff; }
            .btn-pdf:hover { background: #be123c; }
            .btn-close { background: #fff; color: #64748b; border: 1px solid #cbd5e1; }
            .btn-close:hover { background: #f8fafc; color: #334155; }
            
            .report-card { width: 100%; max-width: 860px; background: #fff; padding: 25px 40px 40px; box-shadow: 0 10px 30px rgba(0,0,0,0.1); border-radius: 6px; }
        }

        @media print {
            body { background: #fff; padding: 0; margin: 0; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
            .action-bar { display: none !important; }
            .report-card { padding: 0; margin: 0; width: 100%; max-width: 100%; box-shadow: none; border-radius: 0; }
            .hospital-brand { color: #1793a5 !important; }
            .pdf-page-canvas { width: 100% !important; height: auto !important; page-break-after: always; }
            @page { margin: 10mm; size: A4 portrait; }
        }

        /* Hospital Letterhead Header */
        .hospital-header {
            text-align: center;
            margin-bottom: 12px;
            padding-bottom: 6px;
        }
        .hospital-brand {
            font-size: 26px;
            font-weight: 800;
            color: #1793a5;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            text-transform: uppercase;
            font-family: 'Inter', sans-serif;
            line-height: 1.15;
        }
        .hospital-branches {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 12px;
            font-size: 13px;
            color: #334155;
            font-weight: 600;
            margin-bottom: 5px;
        }
        .hospital-branches::before,
        .hospital-branches::after {
            content: '';
            display: inline-block;
            width: 55px;
            height: 1.5px;
            background: #475569;
        }
        .hospital-subtext {
            font-size: 11px;
            line-height: 1.45;
            color: #1e293b;
            font-weight: 500;
            margin: 0;
        }

        /* Patient Strip */
        .patient-strip {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 8px 16px;
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-left: 4px solid #1793a5;
            border-radius: 6px;
            padding: 10px 14px;
            margin-bottom: 16px;
        }
        .p-field { display: flex; flex-direction: column; }
        .p-lbl { font-size: 10px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        .p-val { font-size: 12px; font-weight: 700; color: #0f172a; word-break: break-word; }

        /* PDF Viewer Container */
        #pdfPagesContainer {
            display: flex;
            flex-direction: column;
            gap: 15px;
            align-items: center;
            width: 100%;
        }
        .pdf-page-wrapper {
            width: 100%;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 2px 8px rgba(0,0,0,0.04);
        }
        .pdf-page-canvas {
            display: block;
            width: 100%;
            height: auto;
        }

        #pdfLoadingSpinner {
            text-align: center;
            padding: 50px 20px;
            color: #1f6b4a;
            font-weight: 700;
            font-size: 14px;
        }
        .spinner {
            border: 4px solid rgba(31, 107, 74, 0.15);
            border-top: 4px solid #1f6b4a;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            animation: spin 0.8s linear infinite;
            margin: 0 auto 12px;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<!-- Top Action Bar -->
<div class="action-bar">
    <button type="button" class="btn btn-close" onclick="handleScanBack()"><i class="fas fa-arrow-left" style="margin-right:6px;"></i> Back</button>
    <button type="button" class="btn btn-pdf" onclick="downloadScanPDF()"><i class="fas fa-file-pdf" style="margin-right:6px;"></i> Download PDF</button>
    <button type="button" class="btn btn-print" onclick="window.print()"><i class="fas fa-print" style="margin-right:6px;"></i> Print</button>
</div>

<!-- Printable Document Wrapper -->
<div class="report-card" id="scanDocumentWrapper">

    <!-- Official Hospital Letterhead Header -->
    <div class="hospital-header">
        <div class="hospital-brand">GM HOSPITALS</div>
        <div class="hospital-branches">Nagarabhavi | Basaveshwaranagar</div>
        <div class="hospital-subtext">
            (A Unit of PANNAGARABHAVI HOSPITALS PVT LTD)<br>
            No. 335, 3rd Stage, 4th Block, Siddaiah Puranik Road,<br>
            Basaveshwara nagar, Bengaluru - 560079<br>
            Tel. No - 0802221160 Mob. No - 9900003527<br>
            GST NO: 29AAFCP8756N3ZE
        </div>
    </div>

    <!-- Patient & Examination Details Strip -->
    <div class="patient-strip">
        <div class="p-field">
            <span class="p-lbl">Patient Name</span>
            <span class="p-val"><?= htmlspecialchars($patientName) ?></span>
        </div>
        <div class="p-field">
            <span class="p-lbl">UHID / Patient ID</span>
            <span class="p-val"><?= htmlspecialchars($patientId) ?></span>
        </div>
        <div class="p-field">
            <span class="p-lbl">Order / Accession No</span>
            <span class="p-val">#<?= htmlspecialchars($orderId) ?> (<?= htmlspecialchars($source) ?>)</span>
        </div>
        <div class="p-field">
            <span class="p-lbl">Age / Gender</span>
            <span class="p-val"><?= htmlspecialchars($genderAge) ?></span>
        </div>
        <div class="p-field">
            <span class="p-lbl">Examination</span>
            <span class="p-val"><?= htmlspecialchars($testName) ?></span>
        </div>
        <div class="p-field">
            <span class="p-lbl">Scan Date</span>
            <span class="p-val"><?= htmlspecialchars($scanDate) ?> <?= htmlspecialchars($scanTime) ?></span>
        </div>
    </div>

    <!-- PDF Pages Container -->
    <div id="pdfLoadingSpinner">
        <div class="spinner"></div>
        <span>Loading Scan Document...</span>
    </div>

    <div id="pdfPagesContainer" style="display:none;"></div>

    <?php if (!$hasPdf): ?>
        <?php if (!empty($row['findings']) || !empty($row['impression'])): ?>
            <div style="background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:16px; margin-top:10px;">
                <?php if (!empty($row['clinical_history'])): ?>
                    <div style="margin-bottom:12px;">
                        <strong style="font-size:11px; text-transform:uppercase; color:#0f172a;">Clinical History:</strong>
                        <div style="font-size:12px; margin-top:3px; color:#1e293b;"><?= nl2br(htmlspecialchars($row['clinical_history'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($row['technique'])): ?>
                    <div style="margin-bottom:12px;">
                        <strong style="font-size:11px; text-transform:uppercase; color:#0f172a;">Technique:</strong>
                        <div style="font-size:12px; margin-top:3px; color:#1e293b;"><?= nl2br(htmlspecialchars($row['technique'])) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($row['findings'])): ?>
                    <div style="margin-bottom:12px;">
                        <strong style="font-size:11px; text-transform:uppercase; color:#0f172a;">Findings / Observations:</strong>
                        <div style="font-size:12px; margin-top:3px; font-family:monospace; white-space:pre-wrap; color:#1e293b; line-height:1.5;"><?= htmlspecialchars($row['findings']) ?></div>
                    </div>
                <?php endif; ?>
                <?php if (!empty($row['impression'])): ?>
                    <div style="background:#e0f2fe; border-left:3px solid #0284c7; padding:10px 14px; border-radius:4px; margin-top:10px;">
                        <strong style="font-size:11px; text-transform:uppercase; color:#0369a1;">Impression / Conclusion:</strong>
                        <div style="font-size:12px; font-weight:700; color:#0f172a; margin-top:3px; line-height:1.4;"><?= nl2br(htmlspecialchars($row['impression'])) ?></div>
                    </div>
                <?php endif; ?>
            </div>
        <?php else: ?>
            <div style="text-align:center; padding: 40px; color:#64748b; font-weight:600; background:#f8fafc; border-radius:6px; border:1px dashed #cbd5e1;">
                <i class="fas fa-file-pdf" style="font-size:2rem; margin-bottom:10px; color:#94a3b8;"></i><br>
                Scan report in progress or awaiting receipt from Somatiq.<br>
                <span style="font-size:0.8rem; font-weight:normal;">Once Somatiq returns the result, the official report will render here automatically.</span>
            </div>
        <?php endif; ?>
    <?php endif; ?>

</div>

<script>
const rawPdfBase64 = <?= json_encode($pdfBase64) ?>;
const orderId = <?= json_encode($orderId) ?>;

async function renderScanPdf() {
    if (!rawPdfBase64) {
        document.getElementById('pdfLoadingSpinner').style.display = 'none';
        return;
    }

    try {
        const binStr = atob(rawPdfBase64);
        const len = binStr.length;
        const bytes = new Uint8Array(len);
        for (let i = 0; i < len; i++) {
            bytes[i] = binStr.charCodeAt(i);
        }

        const loadingTask = pdfjsLib.getDocument({ data: bytes });
        const pdf = await loadingTask.promise;
        const container = document.getElementById('pdfPagesContainer');
        container.innerHTML = '';

        // High resolution scale (2.0 = 300 DPI for crystal clear printing)
        const scale = 2.0;

        for (let pageNum = 1; pageNum <= pdf.numPages; pageNum++) {
            const page = await pdf.getPage(pageNum);
            const viewport = page.getViewport({ scale });

            const pageWrap = document.createElement('div');
            pageWrap.className = 'pdf-page-wrapper';

            const canvas = document.createElement('canvas');
            canvas.className = 'pdf-page-canvas';
            canvas.height = viewport.height;
            canvas.width = viewport.width;

            const ctx = canvas.getContext('2d');
            const renderCtx = {
                canvasContext: ctx,
                viewport: viewport
            };

            await page.render(renderCtx).promise;

            pageWrap.appendChild(canvas);
            container.appendChild(pageWrap);
        }

        document.getElementById('pdfLoadingSpinner').style.display = 'none';
        container.style.display = 'flex';

        // Auto trigger download if requested via ?download=1 or ?pdf=1
        const urlParams = new URLSearchParams(window.location.search);
        if (urlParams.get('download') === '1') {
            setTimeout(downloadScanPDF, 600);
        }

    } catch (err) {
        console.error('Error rendering scan PDF:', err);
        document.getElementById('pdfLoadingSpinner').innerHTML = `
            <div style="color:#e11d48; padding: 20px;">
                <i class="fas fa-exclamation-triangle" style="font-size:2rem; margin-bottom:10px;"></i><br>
                <strong>Unable to render scan PDF preview.</strong><br>
                <span style="font-size:11px; color:#64748b;">The document may be encrypted or corrupted.</span>
            </div>
        `;
    }
}

document.addEventListener('DOMContentLoaded', renderScanPdf);

function downloadScanPDF() {
    const element = document.getElementById('scanDocumentWrapper');
    const btn = document.querySelector('.btn-pdf');
    const origHtml = btn ? btn.innerHTML : '';
    if (btn) {
        btn.disabled = true;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin" style="margin-right:6px;"></i> Generating PDF...';
    }

    const opt = {
        margin:       [8, 8, 8, 8], // mm
        filename:     `Scan_Report_${orderId}.pdf`,
        image:        { type: 'jpeg', quality: 0.98 },
        html2canvas:  { scale: 2, useCORS: true, logging: false },
        jsPDF:        { unit: 'mm', format: 'a4', orientation: 'portrait' }
    };

    html2pdf().set(opt).from(element).save().then(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    }).catch(() => {
        if (btn) {
            btn.disabled = false;
            btn.innerHTML = origHtml;
        }
    });
}

function handleScanBack() {
    const fallbackUrl = <?= json_encode($fallbackBackUrl) ?>;
    if (window.opener && !window.opener.closed) {
        window.close();
    }
    window.close();
    setTimeout(() => {
        if (!window.closed) {
            window.location.href = fallbackUrl;
        }
    }, 120);
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape') handleScanBack();
});
</script>

</body>
</html>
