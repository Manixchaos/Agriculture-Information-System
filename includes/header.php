<?php
$pageTitle = $pageTitle ?? 'Dashboard';
$user = current_user();
$flashMessage = take_flash();
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($pageTitle) ?> · <?= e(envv('APP_NAME', 'AgriTrack')) ?></title>
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
</head>
<body>
<div class="app-shell">
<aside class="sidebar">
  <a class="brand" href="index.php"><span class="brand-mark"><i class="bi bi-flower1"></i></span><span>AgriTrack<small>FIELD MANAGEMENT</small></span></a>
  <div class="nav-label">WORKSPACE</div>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='index.php'?'active':'' ?>" href="index.php"><i class="bi bi-grid-1x2"></i> Dashboard</a>
  <div class="nav-label mt-4">MANAGEMENT</div>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='farmers'?'active':'' ?>" href="module.php?m=farmers" aria-label="Farmers" <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='farmers'?'aria-current="page"':'' ?>><i class="bi bi-people"></i> Farmers</a>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='farms'?'active':'' ?>" href="module.php?m=farms" aria-label="Farms" <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='farms'?'aria-current="page"':'' ?>><i class="bi bi-house"></i> Farms</a>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='crops'?'active':'' ?>" href="module.php?m=crops" aria-label="Crops" <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='crops'?'aria-current="page"':'' ?>><i class="bi bi-flower2"></i> Crops</a>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='plantings'?'active':'' ?>" href="module.php?m=plantings" aria-label="Planting records" <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='plantings'?'aria-current="page"':'' ?>><i class="bi bi-calendar2-week"></i> Planting records</a>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='harvests'?'active':'' ?>" href="module.php?m=harvests" aria-label="Harvest records" <?= basename($_SERVER['PHP_SELF'])==='module.php' && ($_GET['m'] ?? '')==='harvests'?'aria-current="page"':'' ?>><i class="bi bi-basket"></i> Harvest records</a>
  <div class="nav-label mt-4">INSIGHTS</div>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='reports.php'?'active':'' ?>" href="reports.php" aria-label="Reports" <?= basename($_SERVER['PHP_SELF'])==='reports.php'?'aria-current="page"':'' ?>><i class="bi bi-bar-chart"></i> Reports</a>
    <?php if (($user['role'] ?? '') === 'Administrator'): ?>
  <div class="nav-label mt-4">ADMINISTRATION</div>
  <a class="side-link <?= basename($_SERVER['PHP_SELF'])==='users.php'?'active':'' ?>" href="users.php"><i class="bi bi-person-gear"></i> Users</a>
  <?php endif; ?>
  <div class="sidebar-bottom">
    <div class="user-mini"><div class="avatar"><?= e(strtoupper(substr($user['full_name'] ?? 'U',0,1))) ?></div><div><strong><?= e($user['full_name'] ?? '') ?></strong><small><?= e($user['role'] ?? '') ?></small></div></div>
    <a class="side-link logout-link" href="logout.php"><i class="bi bi-box-arrow-left"></i> Sign out</a>
  </div>
</aside>
<main class="main-area">
<header class="topbar"><div><h1><?= e($pageTitle) ?></h1></div><div class="topbar-right"><span class="live-dot"></span><span>Database connected</span><span class="top-date"><?= date('D, d M Y') ?></span></div></header>
<div class="content-wrap">
<?php if ($flashMessage): ?><div class="alert alert-<?= e($flashMessage['type']) ?> alert-dismissible fade show" role="alert"><?= e($flashMessage['message']) ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div><?php endif; ?>
