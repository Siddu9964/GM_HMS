<?php
// vendor_view/includes/head.php
$apiBase = '../../api/'; // Relative path to main API
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">

  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= $pageTitle ?? 'Vendor Portal' ?> — Pharmacy ERP</title>
  <!-- Fonts -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800;900&display=swap" rel="stylesheet">
  <!-- Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <!-- Suppress Favicon 404 & Set Brand Icon -->
  <link rel="icon" href="https://cdn-icons-png.flaticon.com/512/3063/3063822.png">
  <!-- CSS -->
  <link rel="stylesheet" href="assets/css/vendor.css">
  <script>const API_BASE = '<?= $apiBase ?>';</script>
</head>
<body>
