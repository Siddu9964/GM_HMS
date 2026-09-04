<?php
/**
 * quality_head.php — QSC Head Include
 * Identical pattern to lab_head.php / ph_head.php
 * Every quality_view page starts with: require_once __DIR__ . '/../includes/quality_head.php';
 */
if (session_status() === PHP_SESSION_NONE) session_start();
date_default_timezone_set('Asia/Kolkata');
require_once __DIR__ . '/../../core/Autoloader.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: /GM_HMS/login.php');
    exit;
}

// Only admin & quality roles allowed
$_allowedRoles = ['admin', 'Admin', 'Quality_Manager', 'Infection_Control_Nurse'];
if (!in_array($_SESSION['role'] ?? '', $_allowedRoles)) {
    header('Location: /GM_HMS/login.php');
    exit;
}

$pageTitle = $pageTitle ?? 'Quality & Safety';
$pageIcon  = $pageIcon  ?? 'fa-shield-halved';
$pageDesc  = $pageDesc  ?? 'GM Hospital Quality, Safety & Compliance Portal';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
  <title><?= htmlspecialchars($pageTitle) ?> — GM HMS Quality</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Bootstrap 5 -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

  <!-- DataTables -->
  <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
  <link rel="stylesheet" href="https://cdn.datatables.net/buttons/2.4.2/css/buttons.bootstrap5.min.css">

  <!-- Select2 -->
  <link rel="stylesheet" href="/GM_HMS/assets/vendor/select2/select2.min.css">

  <!-- QSC Theme CSS -->
  <link rel="stylesheet" href="/GM_HMS/quality_view/assets/css/quality.css?v=<?= time() ?>">

  <!-- jQuery & Select2 (Loaded in head so all page scripts have immediate access to $) -->
  <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
  <script src="/GM_HMS/assets/vendor/select2/select2.min.js"></script>
</head>
<body>
<script>
  window.HOSPITAL_BRANCH = <?= json_encode($_SESSION['hospital_branch'] ?? '') ?>;
  window.QSC_USER = {
    id:   <?= (int)($_SESSION['user_id'] ?? 0) ?>,
    name: <?= json_encode($_SESSION['name'] ?? 'User') ?>,
    role: <?= json_encode($_SESSION['role'] ?? '') ?>
  };

  /* ─── Shared QSC Helpers ─────────────────────────────────────────────────
     Defined HERE (in the <head>) so every page script can use them
     regardless of where quality_foot.php is included.
  ─────────────────────────────────────────────────────────────────────── */

  /**
   * qscApi — centralised fetch wrapper with auto base-path prefixing
   */
  async function qscApi(endpoint, options = {}) {
    let url = endpoint;
    if (url.startsWith('/api/')) {
      url = '/GM_HMS' + url;
    } else if (url.startsWith('api/')) {
      url = '/GM_HMS/' + url;
    } else if (!url.startsWith('/GM_HMS') && !url.startsWith('http://') && !url.startsWith('https://')) {
      url = '/GM_HMS' + (url.startsWith('/') ? url : '/' + url);
    }
    const headers = {
      'Content-Type': 'application/json',
      'X-Hospital-Branch': window.HOSPITAL_BRANCH ?? ''
    };
    const response = await fetch(url, { headers, ...options });
    const json = await response.json().catch(() => ({ success: false, message: 'Invalid JSON response' }));
    if (!response.ok) {
      throw new Error(json.message ?? json.error ?? 'Request failed (' + response.status + ')');
    }
    return json;
  }

  /**
   * qscToast — lightweight toast notification
   */
  function qscToast(message, type = 'success', duration = 3500) {
    let container = document.getElementById('qsc-toast-container');
    if (!container) {
      container = document.createElement('div');
      container.id = 'qsc-toast-container';
      container.className = 'qsc-toast-container';
      document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = 'qsc-toast ' + type;
    const icons = { success: 'fa-circle-check', error: 'fa-circle-xmark', info: 'fa-circle-info' };
    toast.innerHTML = '<i class="fas ' + (icons[type] ?? 'fa-circle-info') + '" style="margin-right:8px;"></i>' + message;
    container.appendChild(toast);
    setTimeout(() => {
      toast.style.opacity = '0';
      toast.style.transition = 'opacity 0.4s';
      setTimeout(() => toast.remove(), 400);
    }, duration);
  }

  /**
   * formatWeight — display kg value with unit
   */
  function formatWeight(val, unit = 'Kg') {
    return parseFloat(val || 0).toFixed(2) + ' ' + unit;
  }

  /**
   * statusBadge — returns HTML badge string for a status value
   */
  function statusBadge(status) {
    const map = {
      'Collected':  '<span class="status-collected">Collected</span>',
      'Dispatched': '<span class="status-dispatched">Dispatched</span>',
      'Completed':  '<span class="status-completed">Completed</span>'
    };
    return map[status] ?? '<span class="status-collected">' + status + '</span>';
  }

  /**
   * Local DateTime Helpers (Indian Standard Time / Browser Local Time)
   */
  function getLocalDateTimeString(d = new Date()) {
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
  }

  function getLocalDateString(d = new Date()) {
    const pad = n => String(n).padStart(2, '0');
    return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}`;
  }

  function getLocalTimeString(d = new Date()) {
    const pad = n => String(n).padStart(2, '0');
    return `${pad(d.getHours())}:${pad(d.getMinutes())}:${pad(d.getSeconds())}`;
  }
</script>
<div class="qsc-layout">
