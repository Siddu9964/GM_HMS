<?php
/**
 * ot_view/includes/ot_head.php
 * Include at the top of every OT page:
 *   $pageTitle = 'Page Name';
 *   require_once 'includes/ot_head.php';
 */
if (!isset($pageTitle)) $pageTitle = 'GM OT';

$docRoot     = str_replace('\\', '/', $_SERVER['DOCUMENT_ROOT']);
$projectRoot = str_replace('\\', '/', dirname(dirname(__DIR__)));
$baseUrl     = str_ireplace($docRoot, '', $projectRoot);
$apiBase     = rtrim($baseUrl, '/') . '/api/';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($pageTitle) ?> — GM Operation Theatre</title>
<link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/ot.css">
<style>
  :root {
    --ot-primary:    #1f6b4a;
    --ot-sidebar-bg: #1f6b4a;
    --ot-bg:         #f3efe6;
    --ot-surface:    #FDFBF7;
    --ot-text:       #0F172A;
    --ot-border:     #E2E8F0;
    --ot-muted:      #64748B;
    --ot-danger:     #ef4444;
    --ot-success:    #10b981;
    --ot-warning:    #f59e0b;
    --ot-sidebar-w:  180px;
    --ot-navbar-h:   58px;
  }
  html, body { background: var(--ot-bg) !important; font-family: 'Inter', sans-serif; }
  body { overflow-y: auto !important; }
  .ot-wrap, #ot-content { height: auto !important; min-height: 100vh !important; overflow: visible !important; }
</style>
<script>
  const API_BASE = '<?= htmlspecialchars($apiBase) ?>';
</script>
</head>
<body>
<?php
