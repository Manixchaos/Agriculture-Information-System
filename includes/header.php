<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$user = current_user();
$flashMessage = take_flash();
$currentPage = basename($_SERVER['PHP_SELF']);
$currentModule = $_GET['m'] ?? '';
$isModule = fn(string $m): bool => $currentPage === 'module.php' && $currentModule === $m;
$userName = $user['full_name'] ?? '';
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(envv('APP_NAME', 'AgriTrack')) ?></title>
<meta name="description" content="Farm, crop, planting, and harvest records for agricultural operations.">
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
</head>
<body>
<a class="skip-link" href="#main-content">Skip to main content</a>
<div class="app-shell">
<aside class="sidebar">
  <a class="brand" href="index.php" aria-label="AgriTrack dashboard">
    <span class="brand-mark" aria-hidden="true"><i class="bi bi-flower1"></i></span>
    <span>AgriTrack<small>FIELD MANAGEMENT</small></span>
  </a>
  <nav class="side-nav" aria-label="Main navigation">
    <div class="nav-label">Workspace</div>
    <a class="side-link <?= $currentPage === 'index.php' ? 'active' : '' ?>" href="index.php" <?= $currentPage === 'index.php' ? 'aria-current="page"' : '' ?>><i class="bi bi-grid-1x2" aria-hidden="true"></i> Dashboard</a>
    <div class="nav-label mt-4">Management</div>
    <a class="side-link <?= $isModule('farmers') ? 'active' : '' ?>" href="module.php?m=farmers" <?= $isModule('farmers') ? 'aria-current="page"' : '' ?>><i class="bi bi-people" aria-hidden="true"></i> Farmers</a>
    <a class="side-link <?= $isModule('farms') ? 'active' : '' ?>" href="module.php?m=farms" <?= $isModule('farms') ? 'aria-current="page"' : '' ?>><i class="bi bi-map" aria-hidden="true"></i> Farms</a>
    <a class="side-link <?= $isModule('crops') ? 'active' : '' ?>" href="module.php?m=crops" <?= $isModule('crops') ? 'aria-current="page"' : '' ?>><i class="bi bi-flower2" aria-hidden="true"></i> Crops</a>
    <a class="side-link <?= $isModule('plantings') ? 'active' : '' ?>" href="module.php?m=plantings" <?= $isModule('plantings') ? 'aria-current="page"' : '' ?>><i class="bi bi-calendar2-week" aria-hidden="true"></i> Planting records</a>
    <a class="side-link <?= $isModule('harvests') ? 'active' : '' ?>" href="module.php?m=harvests" <?= $isModule('harvests') ? 'aria-current="page"' : '' ?>><i class="bi bi-basket2" aria-hidden="true"></i> Harvest records</a>
    <div class="nav-label mt-4">Insights</div>
    <a class="side-link <?= $currentPage === 'reports.php' ? 'active' : '' ?>" href="reports.php" <?= $currentPage === 'reports.php' ? 'aria-current="page"' : '' ?>><i class="bi bi-bar-chart-line" aria-hidden="true"></i> Reports</a>
    <?php if (($user['role'] ?? '') === 'Administrator'): ?>
    <div class="nav-label mt-4">Administration</div>
    <a class="side-link <?= $currentPage === 'users.php' ? 'active' : '' ?>" href="users.php" <?= $currentPage === 'users.php' ? 'aria-current="page"' : '' ?>><i class="bi bi-person-gear" aria-hidden="true"></i> Users</a>
    <?php endif; ?>
  </nav>
  <div class="sidebar-bottom">
    <div class="user-mini"><div class="avatar" aria-hidden="true"><?= e(initials($userName)) ?></div><div><strong><?= e($userName) ?></strong><small><?= e($user['role'] ?? '') ?></small></div></div>
    <a class="side-link logout-link" href="logout.php" aria-label="Sign out"><i class="bi bi-box-arrow-left" aria-hidden="true"></i> Sign out</a>
  </div>
</aside>
<main class="main-area" id="main-content">
<header class="topbar">
  <div><h1><?= e($pageTitle) ?></h1></div>
  <div class="topbar-right">
    <span class="status-chip"><span class="live-dot" aria-hidden="true"></span>Database connected</span>
    <span class="top-date"><?= date('D, d M Y') ?></span>
  </div>
</header>
<div class="content-wrap">
<?php if ($flashMessage): ?><div class="alert alert-<?= e($flashMessage['type']) ?> alert-dismissible fade show" role="alert"><?= e($flashMessage['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Dismiss message"></button></div><?php endif; ?>
