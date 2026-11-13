<?php
require_once __DIR__ . '/config/app.php';
require_login();
$pageTitle = 'Dashboard';
$pdo = db();
$counts = [];
foreach (['farmers'=>'Farmers','farms'=>'Farms','crops'=>'Crops','plantings'=>'Planting records','harvests'=>'Harvest records'] as $table=>$label) {
    $counts[$table] = (int)$pdo->query("SELECT COUNT(*) FROM {$table}")->fetchColumn();
}
$quantity = (float)$pdo->query("SELECT COALESCE(SUM(quantity_kg),0) FROM harvests")->fetchColumn();
$revenue = (float)$pdo->query("SELECT COALESCE(SUM(quantity_kg * selling_price_per_kg),0) FROM harvests")->fetchColumn();
$statuses = $pdo->query("SELECT ps.status_name, COUNT(p.planting_id) AS total FROM planting_statuses ps LEFT JOIN plantings p ON p.status_id=ps.status_id GROUP BY ps.status_id,ps.status_name ORDER BY ps.status_id")->fetchAll();
$statusTotal = array_sum(array_map(fn($s) => (int)$s['total'], $statuses));
$recentPlantings = $pdo->query("SELECT p.planting_id,p.planting_date,f.farm_name,c.crop_name,ps.status_name FROM plantings p JOIN farms f USING(farm_id) JOIN crops c USING(crop_id) JOIN planting_statuses ps USING(status_id) ORDER BY p.planting_date DESC,p.planting_id DESC LIMIT 6")->fetchAll();
$recentHarvests = $pdo->query("SELECT h.harvest_date,h.quantity_kg,c.crop_name,f.farm_name FROM harvests h JOIN plantings p USING(planting_id) JOIN crops c USING(crop_id) JOIN farms f USING(farm_id) ORDER BY h.harvest_date DESC,h.harvest_id DESC LIMIT 5")->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-intro">
  <p class="page-lead">A clear view of your agricultural operations, with every record counted from the live database.</p>
  <div class="page-tools"><a class="btn btn-primary" href="module.php?m=plantings&action=create"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>New planting</a></div>
</div>

<section class="counts-rail mb-3" aria-label="Record totals">
  <h2 class="visually-hidden">Record totals</h2>
<?php $cards=[['farmers','Farmers','bi-people'],['farms','Farms','bi-map'],['crops','Crop varieties','bi-flower2'],['plantings','Planting records','bi-calendar2-week'],['harvests','Harvest records','bi-basket2']]; foreach($cards as [$key,$label,$icon]): ?>
  <div class="metric"><div class="metric-label"><i class="bi <?= e($icon) ?>" aria-hidden="true"></i><?= e($label) ?></div><div class="metric-value"><?= number_format($counts[$key]) ?></div></div>
<?php endforeach; ?>
</section>

<div class="row g-3 mb-3">
  <div class="col-lg-7">
    <section class="panel h-100 mb-0">
      <div class="panel-head">
        <div><h2 class="section-title">Recent planting records</h2><div class="panel-sub">Latest activity recorded in the system</div></div>
        <a class="panel-link" href="module.php?m=plantings">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
      </div>
      <div class="table-responsive">
        <table class="table">
          <caption class="visually-hidden">Six most recent planting records</caption>
          <thead><tr><th>Crop / Farm</th><th>Planted</th><th>Status</th></tr></thead>
          <tbody>
          <?php foreach($recentPlantings as $r): ?>
            <tr><td><strong><?= e($r['crop_name']) ?></strong><div class="sub"><?= e($r['farm_name']) ?></div></td><td><?= e(nice_date($r['planting_date'])) ?></td><td><span class="badge-soft <?= e(status_tone($r['status_name'])) ?>"><?= e($r['status_name']) ?></span></td></tr>
          <?php endforeach; ?>
          <?php if(!$recentPlantings): ?><tr><td colspan="3"><div class="empty-state"><span class="empty-icon"><i class="bi bi-calendar2-week" aria-hidden="true"></i></span><strong>No planting records yet</strong><p>Record a planting to start tracking growth and expected harvests.</p><a class="btn btn-outline-secondary btn-sm" href="module.php?m=plantings&action=create">Add the first planting</a></div></td></tr><?php endif; ?>
          </tbody>
        </table>
      </div>
    </section>
  </div>
  <div class="col-lg-5 dash-side">
    <section class="harvest-band" aria-label="Harvest totals to date">
      <div class="band-head">
        <div><h2 class="section-title">Harvest to date</h2><div class="panel-sub">All recorded harvest transactions</div></div>
        <span class="band-icon" aria-hidden="true"><i class="bi bi-basket2"></i></span>
      </div>
      <div class="harvest-figures">
        <div class="harvest-figure"><div class="metric-label">Quantity</div><div class="metric-value"><?= number_format($quantity, 0) ?><small>kg</small></div></div>
        <div class="harvest-figure"><div class="metric-label">Estimated value</div><div class="metric-value">₱<?= number_format($revenue, 0) ?></div></div>
      </div>
      <div class="band-foot"><span><?= number_format($counts['harvests']) ?> harvest record<?= $counts['harvests'] === 1 ? '' : 's' ?></span><a class="band-link" href="reports.php">Reports <i class="bi bi-arrow-right" aria-hidden="true"></i></a></div>
    </section>
    <section class="panel mb-0">
      <div class="panel-head"><div><h2 class="section-title">Planting status</h2><div class="panel-sub">Records grouped by current status</div></div></div>
      <?php if ($statuses): ?>
      <div class="status-list">
        <?php foreach($statuses as $s): $st = (int)$s['total']; $pct = $statusTotal > 0 ? round($st / $statusTotal * 100) : 0; $tone = str_replace('tone-', '', status_tone($s['status_name'])); ?>
        <div class="status-row is-<?= e($tone) ?>">
          <div class="status-row-top"><span class="status-name"><?= e($s['status_name']) ?></span><span class="status-count"><?= number_format($st) ?><span>· <?= $pct ?>%</span></span></div>
          <div class="progress"><div class="progress-bar" role="progressbar" style="width:<?= $pct ?>%" aria-label="<?= e($s['status_name']) ?> plantings" aria-valuenow="<?= $st ?>" aria-valuemin="0" aria-valuemax="<?= $statusTotal ?>" aria-valuetext="<?= $st ?> of <?= $statusTotal ?> plantings"></div></div>
        </div>
        <?php endforeach; ?>
      </div>
      <div class="status-total"><span>Total planting records</span><strong><?= number_format($statusTotal) ?></strong></div>
      <?php else: ?>
        <div class="empty-state"><span class="empty-icon"><i class="bi bi-bar-chart" aria-hidden="true"></i></span><strong>No planting statuses</strong><p>Status totals appear once plantings are recorded.</p></div>
      <?php endif; ?>
    </section>
  </div>
</div>

<section class="panel">
  <div class="panel-head">
    <div><h2 class="section-title">Recent harvests</h2><div class="panel-sub">Most recently recorded harvest transactions</div></div>
    <a class="panel-link" href="module.php?m=harvests">View all <i class="bi bi-arrow-right" aria-hidden="true"></i></a>
  </div>
  <div class="table-responsive">
    <table class="table">
      <caption class="visually-hidden">Five most recent harvest records</caption>
      <thead><tr><th>Crop</th><th>Farm</th><th>Harvest date</th><th class="num">Quantity</th></tr></thead>
      <tbody>
      <?php foreach($recentHarvests as $r): ?>
        <tr><td><strong><?= e($r['crop_name']) ?></strong></td><td><?= e($r['farm_name']) ?></td><td><?= e(nice_date($r['harvest_date'])) ?></td><td class="num"><?= number_format((float)$r['quantity_kg'], 2) ?> kg</td></tr>
      <?php endforeach; ?>
      <?php if(!$recentHarvests): ?><tr><td colspan="4"><div class="empty-state"><span class="empty-icon"><i class="bi bi-basket2" aria-hidden="true"></i></span><strong>No harvest records yet</strong><p>Harvest entries appear here as soon as they are recorded.</p><a class="btn btn-outline-secondary btn-sm" href="module.php?m=harvests&action=create">Add the first harvest</a></div></td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
