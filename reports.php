<?php
require_once __DIR__ . '/config/app.php';
require_login();
$pdo = db(); $pageTitle = 'Agricultural reports';
$from = $_GET['from'] ?? date('Y-01-01'); $to = $_GET['to'] ?? date('Y-m-d');
if (!validate_date($from)) $from = date('Y-01-01');
if (!validate_date($to)) $to = date('Y-m-d');
$swapped = false;
if ($from > $to) { [$from, $to] = [$to, $from]; $swapped = true; }

// Farmers and their farms (all farms, with planting counts inside the date range)
$farmsStmt = $pdo->prepare("SELECT f.farm_name, CONCAT(fr.first_name,' ',fr.last_name) farmer_name, f.location, f.area_hectares, COUNT(DISTINCT p.planting_id) planting_count
    FROM farms f JOIN farmers fr ON fr.farmer_id=f.farmer_id
    LEFT JOIN plantings p ON p.farm_id=f.farm_id AND p.planting_date BETWEEN :f AND :t
    GROUP BY f.farm_id, fr.farmer_id ORDER BY f.farm_name");
$farmsStmt->execute(['f' => $from, 't' => $to]); $farms = $farmsStmt->fetchAll();

// Crops planted per farm
$cropsStmt = $pdo->prepare("SELECT f.farm_name, CONCAT(fr.first_name,' ',fr.last_name) farmer_name, c.crop_name, c.variety,
        COUNT(p.planting_id) planting_count, SUM(p.area_planted) total_area
    FROM plantings p JOIN farms f ON f.farm_id=p.farm_id JOIN farmers fr ON fr.farmer_id=f.farmer_id JOIN crops c ON c.crop_id=p.crop_id
    WHERE p.planting_date BETWEEN :f AND :t
    GROUP BY f.farm_id, fr.farmer_id, c.crop_id ORDER BY f.farm_name, c.crop_name");
$cropsStmt->execute(['f' => $from, 't' => $to]); $cropRows = $cropsStmt->fetchAll();

// Planting records by status
$plantings = $pdo->prepare("SELECT ps.status_name, COUNT(*) total FROM plantings p JOIN planting_statuses ps ON ps.status_id=p.status_id WHERE p.planting_date BETWEEN :f AND :t GROUP BY ps.status_name ORDER BY ps.status_name");
$plantings->execute(['f' => $from, 't' => $to]); $statusRows = $plantings->fetchAll();
$plantingTotal = array_sum(array_map(fn($r) => (int)$r['total'], $statusRows));

// Harvest records
$harvest = $pdo->prepare("SELECT h.harvest_date, c.crop_name, f.farm_name, h.quantity_kg, h.selling_price_per_kg, (h.quantity_kg*h.selling_price_per_kg) estimated_value, q.grade_name, hr.remark_description
    FROM harvests h JOIN plantings p ON p.planting_id=h.planting_id JOIN farms f ON f.farm_id=p.farm_id JOIN crops c ON c.crop_id=p.crop_id
    LEFT JOIN quality_grades q ON q.grade_id=h.grade_id LEFT JOIN harvest_remarks hr ON hr.remark_id=h.remark_id
    WHERE h.harvest_date BETWEEN :f AND :t ORDER BY h.harvest_date DESC");
$harvest->execute(['f' => $from, 't' => $to]); $harvestRows = $harvest->fetchAll();
$totalQty = array_sum(array_map(fn($r) => (float)$r['quantity_kg'], $harvestRows));
$totalValue = array_sum(array_map(fn($r) => (float)$r['estimated_value'], $harvestRows));
require __DIR__ . '/includes/header.php';
?>
<div class="print-head"><strong>AgriTrack · Agricultural reports</strong><span><?= e(nice_date($from)) ?> — <?= e(nice_date($to)) ?></span></div>

<div class="page-intro">
  <p class="page-lead">Farm, crop, planting, and harvest activity for the selected date range. Use Print for a clean paper copy.</p>
</div>

<section class="panel">
  <div class="panel-head">
    <div><h2 class="section-title">Report range</h2><div class="panel-sub">The dates apply to planting dates and harvest dates.</div></div>
  </div>
  <?php if ($swapped): ?><div class="alert alert-warning" role="alert">The “From” date was later than the “To” date, so the two dates were swapped.</div><?php endif; ?>
  <form class="report-range" method="get" aria-label="Report date range">
    <div class="field"><label class="form-label" for="report_from">From</label><input class="form-control" id="report_from" type="date" name="from" value="<?= e($from) ?>"></div>
    <div class="field"><label class="form-label" for="report_to">To</label><input class="form-control" id="report_to" type="date" name="to" value="<?= e($to) ?>"></div>
    <div class="field" style="flex:0 0 auto"><span class="visually-hidden">Apply the selected dates</span><button class="btn btn-primary"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply dates</button></div>
    <div class="field" style="flex:0 0 auto"><button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer me-1" aria-hidden="true"></i>Print</button></div>
  </form>
</section>

<div class="metric-rail mb-3" aria-label="Report totals">
  <h2 class="visually-hidden">Report totals</h2>
  <div class="metric"><div class="metric-label">Harvest quantity</div><div class="metric-value"><?= number_format($totalQty, 2) ?><small>kg</small></div></div>
  <div class="metric"><div class="metric-label">Estimated value</div><div class="metric-value">₱<?= number_format($totalValue, 2) ?></div></div>
  <div class="metric"><div class="metric-label">Harvest records</div><div class="metric-value"><?= number_format(count($harvestRows)) ?></div></div>
  <div class="metric"><div class="metric-label">Planting records</div><div class="metric-value"><?= number_format($plantingTotal) ?></div></div>
</div>

<section class="panel">
  <div class="panel-head">
    <div><h2 class="section-title">Farmers and their farms</h2><div class="panel-sub">Farm ownership and planting activity in the selected date range</div></div>
  </div>
  <div class="table-responsive"><table class="table">
    <caption class="visually-hidden">Farmers and their farms</caption>
    <thead><tr><th>Farm</th><th>Farmer</th><th>Location</th><th class="num">Area (ha)</th><th class="num">Plantings</th></tr></thead>
    <tbody><?php foreach ($farms as $r): ?><tr><td><strong><?= e($r['farm_name']) ?></strong></td><td><?= e($r['farmer_name']) ?></td><td><?= e($r['location']) ?></td><td class="num"><?= number_format((float)$r['area_hectares'], 2) ?></td><td class="num"><?= (int)$r['planting_count'] ?></td></tr><?php endforeach; ?><?php if (!$farms): ?><tr><td colspan="5"><div class="empty-state"><span class="empty-icon"><i class="bi bi-map" aria-hidden="true"></i></span><strong>No farm records</strong><p>Farms appear here once they are registered.</p></div></td></tr><?php endif; ?></tbody>
  </table></div>
</section>

<section class="panel">
  <div class="panel-head">
    <div><h2 class="section-title">Crops planted per farm</h2><div class="panel-sub">Number of plantings and total area planted for each crop on each farm</div></div>
  </div>
  <div class="table-responsive"><table class="table">
    <caption class="visually-hidden">Crops planted per farm</caption>
    <thead><tr><th>Farm</th><th>Farmer</th><th>Crop</th><th>Variety</th><th class="num">Plantings</th><th class="num">Area planted (ha)</th></tr></thead>
    <tbody><?php foreach ($cropRows as $r): ?><tr><td><strong><?= e($r['farm_name']) ?></strong></td><td><?= e($r['farmer_name']) ?></td><td><?= e($r['crop_name']) ?></td><td><?= e($r['variety']) ?></td><td class="num"><?= (int)$r['planting_count'] ?></td><td class="num"><?= number_format((float)$r['total_area'], 2) ?></td></tr><?php endforeach; ?><?php if (!$cropRows): ?><tr><td colspan="6"><div class="empty-state"><span class="empty-icon"><i class="bi bi-flower2" aria-hidden="true"></i></span><strong>No plantings in this range</strong><p>Try a wider date range to see planting activity.</p></div></td></tr><?php endif; ?></tbody>
  </table></div>
</section>

<div class="row g-3">
  <div class="col-lg-4">
    <section class="panel h-100 mb-0">
      <div class="panel-head"><div><h2 class="section-title">Planting status</h2><div class="panel-sub">Share of plantings in the range</div></div></div>
      <?php if ($statusRows): ?>
      <div class="status-list">
        <?php foreach ($statusRows as $r): $st = (int)$r['total']; $pct = $plantingTotal > 0 ? round($st / $plantingTotal * 100) : 0; $tone = str_replace('tone-', '', status_tone($r['status_name'])); ?>
        <div class="status-row is-<?= e($tone) ?>">
          <div class="status-row-top"><span class="status-name"><?= e($r['status_name']) ?></span><span class="status-count"><?= number_format($st) ?><span>· <?= $pct ?>%</span></span></div>
          <div class="progress"><div class="progress-bar" role="progressbar" style="width:<?= $pct ?>%" aria-label="<?= e($r['status_name']) ?> plantings" aria-valuenow="<?= $st ?>" aria-valuemin="0" aria-valuemax="<?= $plantingTotal ?>" aria-valuetext="<?= $st ?> of <?= $plantingTotal ?> plantings"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="status-total"><span>Total plantings</span><strong><?= number_format($plantingTotal) ?></strong></div>
      <?php else: ?>
        <div class="empty-state"><span class="empty-icon"><i class="bi bi-bar-chart" aria-hidden="true"></i></span><strong>No plantings in this range</strong><p>Try a wider date range to see status totals.</p></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="col-lg-8">
    <section class="panel mb-0">
      <div class="panel-head">
        <div><h2 class="section-title">Harvest report</h2><div class="panel-sub">Value = quantity × selling price per kilogram</div></div>
      </div>
      <div class="table-responsive"><table class="table">
        <caption class="visually-hidden">Harvest records in the selected date range</caption>
        <thead><tr><th>Date</th><th>Crop / Farm</th><th class="num">Quantity</th><th>Grade</th><th class="num">Value</th></tr></thead>
        <tbody><?php foreach ($harvestRows as $r): ?><tr><td><?= e(nice_date($r['harvest_date'])) ?></td><td><strong><?= e($r['crop_name']) ?></strong><div class="sub"><?= e($r['farm_name']) ?></div></td><td class="num"><?= number_format((float)$r['quantity_kg'], 2) ?> kg</td><td><?= e(($r['grade_name'] ?? '') !== '' ? $r['grade_name'] : '—') ?></td><td class="num">₱<?= number_format((float)$r['estimated_value'], 2) ?></td></tr><?php endforeach; ?><?php if (!$harvestRows): ?><tr><td colspan="5"><div class="empty-state"><span class="empty-icon"><i class="bi bi-basket2" aria-hidden="true"></i></span><strong>No harvests in this range</strong><p>Try a wider date range to see harvest transactions.</p></div></td></tr><?php endif; ?></tbody>
      </table></div>
    </section>
  </div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
