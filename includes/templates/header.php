<?php
/**
 * Master Header Template
 * Construction Materials Shop Management & Accounting System
 */

declare(strict_types=1);

use App\Helpers\Csrf;
use App\Auth\Auth;

$shop = getShop();
$user = Auth::user();
$pageTitle = $pageTitle ?? ($shop['name'] ?? 'Construction ERP');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
  <meta name="csrf-token" content="<?= e(Csrf::getToken()) ?>">
  <title><?= e($pageTitle) ?> | <?= e($shop['name']) ?></title>

  <!-- Prevent Theme Flash (Zero-FOUC) -->
  <script>
    (function() {
      const saved = localStorage.getItem('nirman_erp_theme') || 'system';
      if (saved === 'dark' || (saved === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.setAttribute('data-theme', 'dark');
      } else {
        document.documentElement.setAttribute('data-theme', 'light');
      }
    })();
    const BASE_URL = '<?= BASE_URL ?>';
  </script>

  <!-- Font Awesome 6 (Free CDN) -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" crossorigin="anonymous">

  <!-- Core ERP Styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/app.css?v=2.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/dark-mode.css?v=2.0">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/responsive.css?v=2.1">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/memo-print.css?v=2.0">
</head>
<body>
<div class="app-layout">
