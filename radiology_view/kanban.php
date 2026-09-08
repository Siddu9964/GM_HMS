<?php
$pageTitle = 'Imaging Results & PACS';
$pageIcon  = 'fa-film';
$navTitle  = 'Radiology Results';
$navSub    = 'View, report, edit, and print imaging examinations';
require_once 'includes/rad_head.php';
require_once __DIR__ . '/../models/Database.php';

$db = new \Database();
$db->connect();

$dateFilter = $_GET['date'] ?? 'all';
$isAll = ($dateFilter === 'all');
$sourceFilter = strtoupper($_GET['source'] ?? 'ALL');
$modalityFilter = strtoupper($_GET['modality'] ?? 'ALL');

// Check if dedicated radiology tables exist
$hasRadResults = false;
$hasIpdRadResults = false;
try {
    $r1 = $db->fetchAll("SHOW TABLES LIKE 'radiology_results'");
    $hasRadResults = !empty($r1);
    $r2 = $db->fetchAll("SHOW TABLES LIKE 'ipd_radiology_results'");
    $hasIpdRadResults = !empty($r2);
} catch (Exception $e) {}

$opdTable = $hasRadResults ? 'radiology_results' : 'lab_results';
$ipdTable = $hasIpdRadResults ? 'ipd_radiology_results' : 'ipd_lab_results';

$radRegex = "(^|[^a-zA-Z0-9])(RDS[0-9]*|X-?RAY|X RAY|CT|HRCT|CECT|NCCT|USG|ULTRASOUND|ULTRA SOUND|DOPPLER|MRI|MAMMOGRAPHY|MAMMO)($|[^a-zA-Z0-9])";

$sql = "
    SELECT * FROM (
        SELECT r.result_id, r.order_id, r.patient_id, r.test_name, 
               " . ($hasRadResults ? "r.modality, r.clinical_history, r.technique, r.findings, r.impression," : "'' AS modality, '' AS clinical_history, '' AS technique, '' AS findings, '' AS impression,") . "
               r.result_data, r.abnormal_flags, 
               r.result_date, r.result_time, r.report_file, r.reviewed_by, r.reviewed_at, r.status, 
               r.created_at, r.patient_type,
               CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
               'OPD' AS order_source
        FROM {$opdTable} r
        LEFT JOIN patient p ON r.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
        " . (!$hasRadResults ? "WHERE (r.test_name REGEXP '{$radRegex}')" : "") . "
        
        UNION ALL
        
        SELECT ir.result_id, ir.order_id, ir.patient_id, ir.test_name, 
               " . ($hasIpdRadResults ? "ir.modality, ir.clinical_history, ir.technique, ir.findings, ir.impression," : "'' AS modality, '' AS clinical_history, '' AS technique, '' AS findings, '' AS impression,") . "
               ir.result_data, ir.abnormal_flags, 
               ir.result_date, ir.result_time, ir.report_file, ir.reviewed_by, ir.reviewed_at, ir.status, 
               ir.created_at, ir.patient_type,
               CONCAT(p.first_name, ' ', IFNULL(p.last_name, '')) AS patient_name,
               'IPD' AS order_source
        FROM {$ipdTable} ir
        LEFT JOIN patient p ON ir.patient_id COLLATE utf8mb4_unicode_ci = p.patient_id COLLATE utf8mb4_unicode_ci
        " . (!$hasIpdRadResults ? "WHERE (ir.test_name REGEXP '{$radRegex}')" : "") . "
    ) AS combined_results
";

$whereClauses = [];
$params = [];

if (!$isAll) {
    $whereClauses[] = "result_date = ?";
    $params[] = $dateFilter;
}

if ($sourceFilter === 'OPD' || $sourceFilter === 'IPD') {
    $whereClauses[] = "order_source = ?";
    $params[] = $sourceFilter;
}

if (!empty($whereClauses)) {
    $sql .= " WHERE " . implode(" AND ", $whereClauses);
}

$sql .= " ORDER BY result_date DESC, result_time DESC";

if (!function_exists('isSingleRadiologyTest')) {
    function isSingleRadiologyTest(string $test): bool {
        $t = strtoupper(trim($test));
        if (empty($t)) return false;
        if (preg_match('/\bLAB\d+\b/i', $t)) return false;
        if (preg_match('/\b(CBC|ELECTROLYTE|ELECTROLYTES|UREA|CREATININE|URINE|SUGAR|GLUCOSE|LIPID|BILIRUBIN|WIDAL|CULTURE|HEMOGLOBIN|BLOOD GAS|SEROLOGY|THYROID|STOOL|SPUTUM)\b/i', $t)) {
            if (!preg_match('/\bRDS\d*\b/i', $t)) return false;
        }
        if (preg_match('/\bRDS\d*\b/i', $t)) return true;
        if (preg_match('/\b(X-?RAY|X RAY|CT|HRCT|CECT|NCCT|USG|ULTRASOUND|ULTRA SOUND|DOPPLER|MRI|MAMMOGRAPHY|MAMMO|2D-?ECHO|ECHOCARDIOGRAPHY)\b/i', $t)) {
            return true;
        }
        return false;
    }
}

if (!function_exists('extractOnlyRadiologyTests')) {
    function extractOnlyRadiologyTests($rawTestName): array {
        $items = [];
        if (is_array($rawTestName)) {
            $items = $rawTestName;
        } else {
            $trimmed = trim((string)$rawTestName);
            if ($trimmed !== '' && ($trimmed[0] === '[' || $trimmed[0] === '{')) {
                $decoded = @json_decode($trimmed, true);
                if (is_array($decoded)) {
                    $items = $decoded;
                }
            }
            if (empty($items)) {
                $firstPass = explode('|||', $trimmed);
                foreach ($firstPass as $fp) {
                    $fp = trim($fp);
                    if ($fp === '') continue;
                    // Split on comma after closing parenthesis or before known radiology prefixes
                    $subParts = preg_split('/(?<=\))\s*,\s*|,\s*(?=(?:X-?RAY|CT|HRCT|CECT|NCCT|USG|ULTRASOUND|ULTRA SOUND|MRI|DOPPLER|MAMMO|RDS\d+)\b)/i', $fp);
                    foreach ($subParts as $sp) {
                        $sp = trim($sp);
                        if ($sp !== '') $items[] = $sp;
                    }
                }
            }
        }

        $radTests = [];
        foreach ($items as $it) {
            $testStr = is_array($it) ? ($it['name'] ?? $it['test_name'] ?? '') : (string)$it;
            if (isSingleRadiologyTest($testStr)) {
                $radTests[] = trim($testStr);
            }
        }

        return array_values(array_unique($radTests));
    }
}

if (!function_exists('parseRadiologyItemDetails')) {
    function parseRadiologyItemDetails(string $testStr): array {
        $t = trim($testStr);
        $name = $t;
        $code = '';
        if (preg_match('/^(.*?)\s*\(([A-Za-z0-9_-]+)\)\s*$/', $t, $m)) {
            $name = trim($m[1]);
            $code = trim($m[2]);
        }

        $u = strtoupper($name . ' ' . $code);
        $icon = 'fa-film';
        $modality = 'SCAN';
        $accent = '#1f6b4a';
        $iconBg = '#f3efe6';
        $iconColor = '#1f6b4a';

        if (preg_match('/\b(CT|HRCT|CECT|NCCT)\b/i', $u)) {
            $icon = 'fa-circle-notch';
            $modality = 'CT';
            $accent = '#1f6b4a';
            $iconBg = '#f3efe6';
            $iconColor = '#1f6b4a';
        } elseif (preg_match('/\b(MRI)\b/i', $u)) {
            $icon = 'fa-magnet';
            $modality = 'MRI';
            $accent = '#1f6b4a';
            $iconBg = '#f3efe6';
            $iconColor = '#1f6b4a';
        } elseif (preg_match('/\b(USG|ULTRASOUND|ULTRA SOUND|SONOGRAPHY)\b/i', $u)) {
            $icon = 'fa-wave-square';
            $modality = 'USG';
            $accent = '#1f6b4a';
            $iconBg = '#f3efe6';
            $iconColor = '#1f6b4a';
        } elseif (preg_match('/\b(DOPPLER)\b/i', $u)) {
            $icon = 'fa-heartbeat';
            $modality = 'DOPPLER';
            $accent = '#1f6b4a';
            $iconBg = '#f3efe6';
            $iconColor = '#1f6b4a';
        } elseif (preg_match('/\b(MAMMOGRAPHY|MAMMO)\b/i', $u)) {
            $icon = 'fa-ribbon';
            $modality = 'MAMMO';
            $accent = '#1f6b4a';
            $iconBg = '#f3efe6';
            $iconColor = '#1f6b4a';
        } elseif (preg_match('/\b(X-?RAY|X RAY)\b/i', $u)) {
            $icon = 'fa-x-ray';
            $modality = 'X-RAY';
            $accent = '#1f6b4a';
            $iconBg = '#f3efe6';
            $iconColor = '#1f6b4a';
        }

        return [
            'raw' => $t,
            'name' => $name,
            'code' => $code,
            'modality' => $modality,
            'icon' => $icon,
            'accent' => $accent,
            'iconBg' => $iconBg,
            'iconColor' => $iconColor
        ];
    }
}

$results = [];
try {
    $rawResults = $db->fetchAll($sql, $params);
    foreach ($rawResults as $row) {
        $radList = extractOnlyRadiologyTests($row['test_name']);
        if (empty($radList)) {
            continue;
        }
        $row['rad_tests'] = $radList;
        $row['test_name'] = implode(', ', $radList);
        $results[] = $row;
    }
} catch (Exception $e) {
    $results = [];
}
?>
<?php require_once 'includes/rad_sidebar.php'; ?>

<div class="lis-main-content">
<?php require_once 'includes/rad_navbar.php'; ?>

<div class="lis-content" style="background: #f3efe6; min-height: calc(100vh - var(--lis-navbar-h)); padding: 20px;">

  <!-- Page Header -->
  <div class="lis-page-header lis-fade-up" style="padding-bottom:16px; background: transparent; border-bottom: 1.5px solid #1f6b4a; margin-bottom: 20px;">
    <div style="flex: 1;">
      <div class="lis-page-title" style="display: flex; align-items: center; gap: 14px;">
        <div class="lis-page-title-icon" style="background: #1f6b4a; color: #f3efe6; border: none; box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25); width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.25rem;"><i class="fas fa-film"></i></div>
        <div>
          <span style="color: #1f6b4a; font-weight: 800; font-size: 1.35rem;">Imaging Results & Reports</span>
          <div class="lis-page-subtitle" style="color: #1f6b4a; opacity: 0.82; font-size: 0.85rem; margin-top: 2px;">View radiological impressions, structured findings, and print patient scans</div>
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:12px;flex-wrap:wrap;">
      <div class="kanban-search-wrapper" style="position: relative;">
          <i class="fas fa-search" style="position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #1f6b4a; font-size: 0.85rem;"></i>
          <input type="text" id="resultSearchInput" placeholder="Search patient, scan or ID..." class="lis-input" style="padding-left: 34px; border-radius: 20px; width: 220px; border: 1.5px solid #1f6b4a; background: #f3efe6; color: #1f6b4a; font-weight: 600;" autocomplete="off" onkeyup="filterResults()">
      </div>
      <select id="result-modality" class="lis-input lis-select" style="width:auto; border-radius: 20px; border: 1.5px solid #1f6b4a; background: #f3efe6; color: #1f6b4a; font-weight: 600;" onchange="filterResults()">
        <option value="ALL">All Modalities</option>
        <option value="X-RAY">X-Ray</option>
        <option value="CT">CT Scan</option>
        <option value="ULTRA SOUND">Ultrasound</option>
        <option value="DOPPLER">Doppler</option>
      </select>
      <select id="result-source" class="lis-input lis-select" style="width:auto; border-radius: 20px; border: 1.5px solid #1f6b4a; background: #f3efe6; color: #1f6b4a; font-weight: 600;" onchange="changeSource()">
        <option value="ALL" <?= $sourceFilter === 'ALL' ? 'selected' : '' ?>>All Sources (OPD & IPD)</option>
        <option value="OPD" <?= $sourceFilter === 'OPD' ? 'selected' : '' ?>>OPD Only</option>
        <option value="IPD" <?= $sourceFilter === 'IPD' ? 'selected' : '' ?>>IPD Only</option>
      </select>
      <select id="result-date" class="lis-input lis-select" style="width:auto; border-radius: 20px; border: 1.5px solid #1f6b4a; background: #f3efe6; color: #1f6b4a; font-weight: 600;" onchange="changeDate()">
        <option value="<?= date('Y-m-d') ?>" <?= $dateFilter === date('Y-m-d') ? 'selected' : '' ?>>Today</option>
        <option value="all" <?= $isAll ? 'selected' : '' ?>>All Time</option>
      </select>
      <a href="kanban.php<?= $sourceFilter !== 'ALL' ? '?source=' . strtolower($sourceFilter) : '' ?>" class="lis-btn" style="border-radius: 20px; border: 1.5px solid #1f6b4a; color: #1f6b4a; background: #f3efe6; font-weight: 700; padding: 8px 18px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
        <i class="fas fa-sync-alt"></i> Refresh
      </a>
      <a href="test_orders.php" class="lis-btn" style="border-radius: 20px; background: #1f6b4a; color: #f3efe6; border: 1.5px solid #1f6b4a; font-weight: 700; padding: 8px 18px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 4px 12px rgba(31, 107, 74, 0.25);">
        <i class="fas fa-plus"></i> New Scan Order
      </a>
    </div>
  </div>

  <!-- Results Table -->
  <div class="lis-card lis-fade-up-1" style="border-radius: 14px; border: 1.5px solid #1f6b4a; box-shadow: 0 6px 20px rgba(31, 107, 74, 0.08); background: #f3efe6; overflow: hidden;">
    <div class="lis-card-body" style="padding: 0; overflow-x: auto;">
        <table class="lis-table" id="resultsTable" style="margin: 0; width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #1f6b4a; color: #f3efe6;">
                    <th style="padding: 14px 16px; color: #f3efe6; font-weight: 700; border-bottom: 1.5px solid #1f6b4a;">Scan Date & Time</th>
                    <th style="padding: 14px 16px; color: #f3efe6; font-weight: 700; border-bottom: 1.5px solid #1f6b4a;">Order & Modality</th>
                    <th style="padding: 14px 16px; color: #f3efe6; font-weight: 700; border-bottom: 1.5px solid #1f6b4a;">Patient Details</th>
                    <th style="padding: 14px 16px; color: #f3efe6; font-weight: 700; border-bottom: 1.5px solid #1f6b4a;">Procedure / Examination</th>
                    <th style="padding: 14px 16px; color: #f3efe6; font-weight: 700; border-bottom: 1.5px solid #1f6b4a;">Status & Impressions</th>
                    <th style="text-align: right; padding: 14px 16px; color: #f3efe6; font-weight: 700; border-bottom: 1.5px solid #1f6b4a;">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($results)): ?>
                <tr style="background: #f3efe6;">
                    <td colspan="6" style="text-align: center; padding: 50px 20px; color: #1f6b4a;">
                        <i class="fas fa-film" style="font-size: 2.5rem; margin-bottom: 12px; color: #1f6b4a; opacity: 0.4;"></i><br>
                        <strong style="font-size: 1rem; color: #1f6b4a;">No radiology results found</strong>
                        <p style="margin: 4px 0 0 0; font-size: 0.85rem; color: #1f6b4a; opacity: 0.8;">Completed <?= htmlspecialchars($sourceFilter === 'ALL' ? '' : $sourceFilter . ' ') ?>radiology reports will appear here automatically.</p>
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($results as $row): 
                        $testName = $row['test_name'];
                        $tests = !empty($row['rad_tests']) ? $row['rad_tests'] : [$testName];
                        
                        // Parse each test and collect modalities
                        $parsedTests = [];
                        $modalities = [];
                        foreach ($tests as $tItem) {
                            $p = parseRadiologyItemDetails($tItem);
                            $parsedTests[] = $p;
                            $modalities[] = $p['modality'];
                        }
                        $modalities = array_values(array_unique($modalities));
                        
                        $mod = strtoupper($row['modality'] ?? '');
                        if (empty($mod)) {
                            $mod = !empty($modalities) ? implode(', ', $modalities) : 'X-RAY';
                        }
                        
                        $status = $row['status'] ?? 'Reported';
                    ?>
                    <tr class="result-row" 
                        style="background: #f3efe6; border-bottom: 1px solid rgba(31, 107, 74, 0.2);"
                        data-search="<?= htmlspecialchars(strtolower($row['order_id'] . ' ' . $row['patient_name'] . ' ' . $testName . ' ' . implode(' ', $modalities) . ' ' . ($row['patient_id'] ?? ''))) ?>" 
                        data-source="<?= htmlspecialchars($row['order_source'] ?? 'OPD') ?>"
                        data-modality="<?= htmlspecialchars(implode(' ', $modalities)) ?>">
                        <td style="padding: 14px 16px; font-weight: 600; color: #1f6b4a; vertical-align: top;">
                            <?= htmlspecialchars(date('d M Y', strtotime($row['result_date']))) ?>
                            <div style="font-size: 0.75rem; color: #1f6b4a; opacity: 0.8;"><i class="far fa-clock"></i> <?= htmlspecialchars(substr($row['result_time'] ?? '00:00', 0, 5)) ?></div>
                        </td>
                        <td style="padding: 14px 16px; vertical-align: top;">
                            <div style="display:flex;align-items:center;gap:6px;margin-bottom:5px;flex-wrap:wrap;">
                                <span style="font-weight: 800; color: #1f6b4a;">#<?= htmlspecialchars($row['order_id']) ?></span>
                                <?php if (($row['order_source'] ?? '') === 'IPD'): ?>
                                    <span class="lis-badge" style="background:#1f6b4a;color:#f3efe6;border:1.5px solid #1f6b4a;font-size:0.65rem;padding:2px 8px;font-weight:700;border-radius:10px;">IPD</span>
                                <?php else: ?>
                                    <span class="lis-badge" style="background:#f3efe6;color:#1f6b4a;border:1.5px solid #1f6b4a;font-size:0.65rem;padding:2px 8px;font-weight:700;border-radius:10px;">OPD</span>
                                <?php endif; ?>
                                <?php if (count($tests) > 1): ?>
                                    <span class="lis-badge" style="background:#f3efe6;color:#1f6b4a;font-size:0.65rem;padding:2px 8px;border:1px solid #1f6b4a;font-weight:600;" title="<?= count($tests) ?> examinations in this order">
                                        <i class="fas fa-layer-group"></i> <?= count($tests) ?> Scans
                                    </span>
                                <?php endif; ?>
                            </div>
                            <div style="display:flex; flex-wrap:wrap; gap:4px;">
                                <?php foreach ($modalities as $mItem): ?>
                                    <span class="lis-badge" style="background:#f3efe6;color:#1f6b4a;font-size:0.68rem;padding:2px 8px;border:1px solid #1f6b4a;font-weight:600;">
                                        <i class="fas fa-camera"></i> <?= htmlspecialchars($mItem) ?>
                                    </span>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td style="padding: 14px 16px; vertical-align: top;">
                            <div style="display: flex; align-items: center; gap: 10px;">
                                <div style="width: 34px; height: 34px; border-radius: 8px; background: #1f6b4a; color: #f3efe6; border: 1.5px solid #1f6b4a; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 0.85rem; flex-shrink: 0;">
                                    <?= htmlspecialchars(substr($row['patient_name'] ?? 'P', 0, 1)) ?>
                                </div>
                                <div>
                                    <div style="font-weight: 700; color: #1f6b4a; font-size: 0.9rem;"><?= htmlspecialchars($row['patient_name'] ?? 'Unknown Patient') ?></div>
                                    <div style="font-size: 0.75rem; color: #1f6b4a; opacity: 0.8; font-weight: 600;">UHID: <?= htmlspecialchars($row['patient_id']) ?></div>
                                </div>
                            </div>
                        </td>
                        <td style="padding: 14px 16px; vertical-align: top;">
                            <div class="rad-tests-stack" style="display: flex; flex-direction: column; gap: 6px; min-width: 250px; max-width: 480px;">
                                <?php foreach ($parsedTests as $pTest): ?>
                                    <div class="rad-test-card" style="display: flex; align-items: center; justify-content: space-between; gap: 10px; background: #f3efe6; border: 1.5px solid #1f6b4a; border-left: 4px solid #1f6b4a; border-radius: 6px; padding: 7px 10px; transition: all 0.15s ease;">
                                        <div style="display: flex; align-items: center; gap: 8px; min-width: 0; flex: 1;">
                                            <span style="display: inline-flex; align-items: center; justify-content: center; width: 24px; height: 24px; border-radius: 5px; background: #1f6b4a; color: #f3efe6; font-size: 0.72rem; flex-shrink: 0;">
                                                <i class="fas <?= $pTest['icon'] ?>"></i>
                                            </span>
                                            <span style="font-weight: 700; font-size: 0.82rem; color: #1f6b4a; line-height: 1.25; word-break: break-word;">
                                                <?= htmlspecialchars($pTest['name']) ?>
                                            </span>
                                        </div>
                                        <?php if (!empty($pTest['code'])): ?>
                                            <span style="display: inline-flex; align-items: center; font-family: ui-monospace, monospace; font-size: 0.68rem; font-weight: 700; color: #1f6b4a; background: #f3efe6; border: 1px solid #1f6b4a; border-radius: 4px; padding: 2px 6px; white-space: nowrap; flex-shrink: 0;" title="Service ID">
                                                <?= htmlspecialchars($pTest['code']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <?php if (!empty($row['technique'])): ?>
                                <div style="font-size:0.75rem; color:#1f6b4a; opacity: 0.85; margin-top:6px; display: flex; align-items: center; gap: 4px;">
                                    <span style="font-weight:700; color:#1f6b4a;"><i class="fas fa-sliders-h" style="font-size:0.7rem;"></i> Tech:</span> 
                                    <span><?= htmlspecialchars(substr($row['technique'], 0, 50)) ?><?= strlen($row['technique']) > 50 ? '...' : '' ?></span>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="padding: 14px 16px; vertical-align: top;">
                            <span class="lis-badge" style="background:#1f6b4a;color:#f3efe6;border:1.5px solid #1f6b4a;font-weight:700;padding:4px 10px;border-radius:8px;display:inline-block;"><?= htmlspecialchars($status) ?></span>
                            <?php if (!empty($row['impression'])): ?>
                                <div style="font-size:0.75rem;color:#1f6b4a;font-weight:600;max-width:260px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;margin-top:5px;" title="<?= htmlspecialchars($row['impression']) ?>">
                                    <i class="fas fa-stethoscope"></i> <?= htmlspecialchars($row['impression']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; padding: 14px 16px; vertical-align: top; white-space: nowrap;">
                            <button type="button" onclick="editRadResult('<?= htmlspecialchars($row['order_id']) ?>', '<?= htmlspecialchars($row['order_source'] ?? 'OPD') ?>', <?= htmlspecialchars(json_encode($tests), ENT_QUOTES) ?>)" class="lis-btn" style="padding: 5px 12px; font-size: 0.75rem; border: 1.5px solid #1f6b4a; color: #1f6b4a; margin-right: 4px; background:#f3efe6; font-weight:700; border-radius:6px; cursor:pointer;">
                                <i class="fas fa-edit"></i> Edit
                            </button>
                            <a href="print_result.php?order_id=<?= urlencode($row['order_id']) ?>&source=<?= urlencode($row['order_source'] ?? 'OPD') ?>&from=kanban" target="_blank" onclick="window.open(this.href, '_blank'); return false;" class="lis-btn" style="padding: 5px 12px; font-size: 0.75rem; border: 1.5px solid #1f6b4a; color: #f3efe6; background:#1f6b4a; font-weight:700; border-radius:6px; text-decoration:none; display:inline-block;">
                                <i class="fas fa-print"></i> Report
                            </a>
                            <?php if (!empty($row['report_file'])): ?>
                                <a href="/GM_HMS/<?= htmlspecialchars($row['report_file']) ?>" target="_blank" class="lis-btn" style="padding: 5px 8px; font-size: 0.75rem; border: 1.5px solid #1f6b4a; color: #1f6b4a; background:#f3efe6; border-radius:6px; text-decoration:none; margin-left:4px; display:inline-block;" title="View Scan File">
                                    <i class="fas fa-file-image"></i>
                                </a>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
  </div>

</div>
</div>

<!-- Structured Radiology Edit Modal -->
<div class="modal fade" id="radEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered">
    <div class="modal-content" style="border-radius:14px;border:2px solid #1f6b4a;box-shadow:0 20px 40px rgba(31,107,74,0.25);overflow:hidden;background:#f3efe6;">
      <div class="modal-header" style="background:#1f6b4a;border-bottom:1.5px solid rgba(243,239,230,0.2);padding:16px 24px;color:#f3efe6;">
        <h5 class="modal-title" style="font-weight:700;color:#f3efe6;display:flex;align-items:center;gap:8px;">
          <i class="fas fa-edit"></i> Edit Radiology Report #<span id="em-order-id"></span>
        </h5>
        <button type="button" class="btn-close btn-close-white" onclick="closeRadEditModal()" data-bs-dismiss="modal" aria-label="Close" style="cursor:pointer;"></button>
      </div>
      <div class="modal-body" style="padding:24px;background:#f3efe6;">
        <!-- Attached Scan File Card -->
        <div id="em-file-preview-card" style="display:none;background:#f3efe6;border-radius:10px;padding:12px 16px;margin-bottom:14px;border:1.5px solid #1f6b4a;">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <span style="font-size:0.78rem;font-weight:700;color:#1f6b4a;text-transform:uppercase;display:flex;align-items:center;gap:6px;">
                    <i class="fas fa-paperclip"></i> Attached Scan Card
                </span>
                <button type="button" class="btn btn-sm" onclick="removeKanbanAttachedFile()" title="Close / Remove Attachment" style="padding:3px 10px;font-size:0.75rem;border-radius:6px;background:#1f6b4a;color:#f3efe6;border:1px solid #1f6b4a;font-weight:700;cursor:pointer;">
                    <i class="fas fa-times"></i> Close Card
                </button>
            </div>
            <div id="em-file-name-label" style="font-size:0.82rem;font-weight:600;margin-top:6px;color:#1f6b4a;"></div>
            <img id="em-file-preview-img" style="max-width:100%;max-height:220px;border-radius:6px;margin-top:8px;display:none;object-fit:contain;border:1.5px solid #1f6b4a;">
        </div>

        <div id="em-procedures-banner" style="display:none; margin-bottom:16px; padding:12px 14px; background:#f3efe6; border:1.5px solid #1f6b4a; border-radius:8px;">
            <div style="font-size:0.75rem; font-weight:700; color:#1f6b4a; text-transform:uppercase; letter-spacing:0.5px; margin-bottom:8px;">
                <i class="fas fa-layer-group"></i> Ordered Examination(s):
            </div>
            <div id="em-procedures-list" style="display:flex; flex-direction:column; gap:6px;"></div>
        </div>
        <div id="em-loading" style="text-align:center;padding:30px;color:#1f6b4a;font-weight:600;">
          <div class="lis-spinner" style="margin:0 auto 10px;border:3px solid rgba(31,107,74,0.2);border-top-color:#1f6b4a;width:32px;height:32px;border-radius:50%;animation:lisSpin 0.8s linear infinite;"></div>
          Loading examination details...
        </div>
        <form id="em-form" style="display:none;" onsubmit="saveRadReport(event)">
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
            <div>
              <label class="lis-form-label" style="color:#1f6b4a;font-weight:700;font-size:0.82rem;margin-bottom:4px;display:block;">Clinical History & Indications</label>
              <textarea class="lis-input" id="em-history" rows="2" style="border:1.5px solid #1f6b4a;color:#1f6b4a;background:#f3efe6;border-radius:6px;padding:8px;width:100%;box-sizing:border-box;" placeholder="e.g., Cough, chest pain, trauma..."></textarea>
            </div>
            <div>
              <label class="lis-form-label" style="color:#1f6b4a;font-weight:700;font-size:0.82rem;margin-bottom:4px;display:block;">Technique / Protocol</label>
              <textarea class="lis-input" id="em-technique" rows="2" style="border:1.5px solid #1f6b4a;color:#1f6b4a;background:#f3efe6;border-radius:6px;padding:8px;width:100%;box-sizing:border-box;" placeholder="e.g., Digital PA view erect position..."></textarea>
            </div>
          </div>
          <div style="margin-bottom:14px;">
            <label class="lis-form-label" style="color:#1f6b4a;font-weight:700;font-size:0.82rem;margin-bottom:4px;display:block;">Findings / Observations</label>
            <textarea class="lis-input" id="em-findings" rows="4" style="font-family:monospace;font-size:0.9rem;border:1.5px solid #1f6b4a;color:#1f6b4a;background:#f3efe6;border-radius:6px;padding:8px;width:100%;box-sizing:border-box;" placeholder="Detailed anatomical findings..."></textarea>
          </div>
          <div style="margin-bottom:14px;">
            <label class="lis-form-label" style="color:#1f6b4a;font-weight:700;font-size:0.82rem;margin-bottom:4px;display:block;">Impression / Conclusion</label>
            <textarea class="lis-input" id="em-impression" rows="2" style="border:1.5px solid #1f6b4a;color:#1f6b4a;background:#f3efe6;border-radius:6px;padding:8px;width:100%;box-sizing:border-box;" placeholder="Final diagnostic conclusion..."></textarea>
          </div>
          <div style="display:grid;grid-template-columns:1fr 1fr;gap:16px;margin-bottom:14px;">
            <div>
              <label class="lis-form-label" style="color:#1f6b4a;font-weight:700;font-size:0.82rem;margin-bottom:4px;display:block;">Status</label>
              <select class="lis-input lis-select" id="em-status" style="border:1.5px solid #1f6b4a;color:#1f6b4a;background:#f3efe6;border-radius:6px;padding:8px;width:100%;box-sizing:border-box;font-weight:600;">
                <option value="Completed">Completed</option>
                <option value="Reported">Reported</option>
                <option value="Reviewed">Reviewed</option>
                <option value="Critical">Critical Finding (Urgent)</option>
              </select>
            </div>
            <div>
              <label class="lis-form-label" style="color:#1f6b4a;font-weight:700;font-size:0.82rem;margin-bottom:4px;display:block;">Reporting Radiologist</label>
              <input type="text" class="lis-input" id="em-doctor" style="border:1.5px solid #1f6b4a;color:#1f6b4a;background:#f3efe6;border-radius:6px;padding:8px;width:100%;box-sizing:border-box;" placeholder="Dr. Radiologist Name">
            </div>
          </div>
          <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:20px;">
            <button type="button" class="lis-btn" style="border:1.5px solid #1f6b4a;color:#1f6b4a;background:#f3efe6;font-weight:700;border-radius:6px;padding:8px 16px;cursor:pointer;" onclick="closeRadEditModal()" data-bs-dismiss="modal">Cancel</button>
            <button type="submit" class="lis-btn" style="background:#1f6b4a;color:#f3efe6;border:1.5px solid #1f6b4a;font-weight:700;border-radius:6px;padding:8px 18px;cursor:pointer;" id="em-btn-save">
              <i class="fas fa-save"></i> Save Changes
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<style>
@keyframes lisSpin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}
#resultsTable tbody tr {
    transition: background-color 0.2s;
    background-color: #f3efe6;
}
#resultsTable tbody tr:hover {
    background-color: rgba(31, 107, 74, 0.08) !important;
}
.rad-test-card {
    transition: all 0.15s ease-in-out;
}
.rad-test-card:hover {
    background-color: #1f6b4a !important;
    color: #f3efe6 !important;
    transform: translateY(-1px);
}
.rad-test-card:hover span {
    color: #f3efe6 !important;
}
</style>

<script>
function changeSource() {
    const src = document.getElementById('result-source').value;
    const date = document.getElementById('result-date').value;
    const params = [];
    if (src && src !== 'ALL') params.push('source=' + encodeURIComponent(src.toLowerCase()));
    if (date && date !== 'all') params.push('date=' + encodeURIComponent(date));
    window.location.href = 'kanban.php' + (params.length ? '?' + params.join('&') : '');
}

function changeDate() {
    const date = document.getElementById('result-date').value;
    const src = document.getElementById('result-source').value;
    const params = [];
    if (src && src !== 'ALL') params.push('source=' + encodeURIComponent(src.toLowerCase()));
    if (date && date !== 'all') params.push('date=' + encodeURIComponent(date));
    window.location.href = 'kanban.php' + (params.length ? '?' + params.join('&') : '');
}

function filterResults() {
    const query = document.getElementById('resultSearchInput').value.toLowerCase().trim();
    const sourceFilter = document.getElementById('result-source').value;
    const modFilter = document.getElementById('result-modality').value;
    const rows = document.querySelectorAll('.result-row');
    
    rows.forEach(row => {
        const matchesQuery = !query || row.dataset.search.includes(query);
        const matchesSource = sourceFilter === 'ALL' || row.dataset.source === sourceFilter;
        const matchesMod = modFilter === 'ALL' || (row.dataset.modality && row.dataset.modality.includes(modFilter));
        
        if (matchesQuery && matchesSource && matchesMod) {
            row.style.display = 'table-row';
        } else {
            row.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    filterResults();
});

let currentEditId = null;
let currentEditSource = 'OPD';
let radEditBsModal = null;

async function editRadResult(orderId, source = 'OPD', testsList = null) {
    if (!radEditBsModal) {
        radEditBsModal = new bootstrap.Modal(document.getElementById('radEditModal'));
    }
    currentEditId = orderId;
    currentEditSource = source;
    document.getElementById('em-order-id').textContent = orderId;
    document.getElementById('em-loading').style.display = 'block';
    document.getElementById('em-form').style.display = 'none';

    // Show ordered procedures in modal banner
    const banner = document.getElementById('em-procedures-banner');
    const pList = document.getElementById('em-procedures-list');
    if (banner && pList) {
        let tArr = [];
        if (Array.isArray(testsList)) tArr = testsList;
        else if (typeof testsList === 'string') {
            try { tArr = JSON.parse(testsList); } catch(e) { tArr = [testsList]; }
        }
        if (tArr && tArr.length > 0) {
            banner.style.display = 'block';
            pList.innerHTML = tArr.map(t => `
                <div style="display:flex; align-items:center; gap:8px; font-size:0.82rem; font-weight:700; color:#1f6b4a; background:#f3efe6; padding:6px 12px; border-radius:6px; border:1.5px solid #1f6b4a;">
                    <i class="fas fa-check-circle" style="color:#1f6b4a; font-size:0.85rem;"></i>
                    <span>${t}</span>
                </div>
            `).join('');
        } else {
            banner.style.display = 'none';
            pList.innerHTML = '';
        }
    }

    radEditBsModal.show();

    try {
        const apiBase = (source === 'IPD') ? '/GM_HMS/api/radiology/ipd-orders/' : '/GM_HMS/api/radiology/orders/';
        const res = await fetch(apiBase + encodeURIComponent(orderId) + '/result');
        const data = await res.json();
        
        document.getElementById('em-loading').style.display = 'none';
        document.getElementById('em-form').style.display = 'block';

        if (data.success && data.data) {
            const r = data.data;
            document.getElementById('em-history').value = r.clinical_history || '';
            document.getElementById('em-technique').value = r.technique || '';
            document.getElementById('em-findings').value = r.findings || (r.result_data ? JSON.stringify(r.result_data) : '');
            document.getElementById('em-impression').value = r.impression || '';
            document.getElementById('em-status').value = r.status || 'Completed';
            document.getElementById('em-doctor').value = r.reviewed_by || '';

            if (r.report_file) {
                document.getElementById('em-file-preview-card').style.display = 'block';
                const fName = r.report_file.split('/').pop();
                document.getElementById('em-file-name-label').innerHTML = `<a href="/GM_HMS/${encodeURI(r.report_file)}" target="_blank" style="color:#1f6b4a;font-weight:700;text-decoration:underline;"><i class="fas fa-file-medical"></i> ${fName}</a>`;
                if (r.report_file.match(/\.(jpeg|jpg|png|gif|webp)$/i)) {
                    const img = document.getElementById('em-file-preview-img');
                    img.src = '/GM_HMS/' + r.report_file;
                    img.style.display = 'block';
                } else {
                    document.getElementById('em-file-preview-img').style.display = 'none';
                    document.getElementById('em-file-preview-img').src = '';
                }
            } else {
                removeKanbanAttachedFile();
            }
        } else {
            document.getElementById('em-history').value = '';
            document.getElementById('em-technique').value = '';
            document.getElementById('em-findings').value = '';
            document.getElementById('em-impression').value = '';
            document.getElementById('em-status').value = 'Completed';
            document.getElementById('em-doctor').value = '';
            removeKanbanAttachedFile();
        }
    } catch (e) {
        document.getElementById('em-loading').innerHTML = '<div style="color:#1f6b4a;font-weight:700;">Error loading report data.</div>';
    }
}

function removeKanbanAttachedFile() {
    const card = document.getElementById('em-file-preview-card');
    if (card) card.style.display = 'none';
    const lbl = document.getElementById('em-file-name-label');
    if (lbl) lbl.innerHTML = '';
    const img = document.getElementById('em-file-preview-img');
    if (img) {
        img.style.display = 'none';
        img.src = '';
    }
}

function closeRadEditModal() {
    if (typeof Swal !== 'undefined' && Swal.isVisible()) {
        Swal.close();
    }
    const modalEl = document.getElementById('radEditModal');
    if (modalEl) {
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            const inst = bootstrap.Modal.getInstance(modalEl) || radEditBsModal;
            if (inst) {
                try { inst.hide(); } catch(e) {}
            }
        }
        modalEl.classList.remove('show');
        modalEl.style.display = 'none';
        modalEl.setAttribute('aria-hidden', 'true');
        modalEl.removeAttribute('aria-modal');
    }
    document.body.classList.remove('modal-open');
    document.body.style.removeProperty('overflow');
    document.body.style.removeProperty('padding-right');
    document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
}

// Backdrop click and Esc key handlers
const radModalEl = document.getElementById('radEditModal');
if (radModalEl) {
    radModalEl.addEventListener('click', function(e) {
        if (e.target === this) closeRadEditModal();
    });
}

document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeRadEditModal();
    }
});

async function saveRadReport(e) {
    e.preventDefault();
    const btn = document.getElementById('em-btn-save');
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';

    const payload = {
        clinical_history: document.getElementById('em-history').value.trim(),
        technique: document.getElementById('em-technique').value.trim(),
        findings: document.getElementById('em-findings').value.trim(),
        impression: document.getElementById('em-impression').value.trim(),
        status: document.getElementById('em-status').value,
        reporting_radiologist: document.getElementById('em-doctor').value.trim()
    };

    try {
        const apiBase = (currentEditSource === 'IPD') ? '/GM_HMS/api/radiology/ipd-orders/' : '/GM_HMS/api/radiology/orders/';
        const res = await fetch(apiBase + encodeURIComponent(currentEditId) + '/result', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        });
        const d = await res.json();
        if (d.success) {
            radToast('Radiology report updated successfully', 'success');
            setTimeout(() => location.reload(), 800);
        } else {
            radToast(d.message || 'Error updating report', 'error');
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
        }
    } catch(err) {
        radToast('Network error while saving', 'error');
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-save"></i> Save Changes';
    }
}
</script>

<?php require_once 'includes/rad_foot.php'; ?>
