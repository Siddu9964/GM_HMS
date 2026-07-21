<?php
/**
 * LIS Head Include — lab_head.php
 * Outputs the full <head> + opens <body> and starts the layout wrapper.
 * Every laboratory_view page starts with: <?php require_once 'includes/lab_head.php'; ?>
 */
if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: /GM_HMS/login.php');
    exit;
}
$pageTitle  = $pageTitle  ?? 'Laboratory';
$pageIcon   = $pageIcon   ?? 'fa-microscope';
$pageDesc   = $pageDesc   ?? 'GM Hospital Laboratory Information System';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="description" content="<?= htmlspecialchars($pageDesc) ?>">
  <title><?= htmlspecialchars($pageTitle) ?> — GM HMS Laboratory</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- Bootstrap 5 CSS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">

  <!-- LIS CSS -->
  <link rel="stylesheet" href="/GM_HMS/laboratory_view/assets/css/laboratory.css?v=<?= time() ?>">
</head>
<body>
<script>
  // Branch identifier used by lisApi() to send X-Hospital-Branch header on every request
  window.HOSPITAL_BRANCH = <?= json_encode($_SESSION['hospital_branch'] ?? '') ?>;
</script>
<div class="lis-layout">
