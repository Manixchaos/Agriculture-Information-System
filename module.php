<?php
require_once __DIR__ . '/config/app.php';
require_login();
$all = require __DIR__ . '/modules.php';
$m = $_GET['m'] ?? 'farmers';
if (!isset($all[$m])) { http_response_code(404); exit('Module not found.'); }
$cfg = $all[$m]; $pdo = db(); $pk = $cfg['pk']; $table = $cfg['table']; $alias = $cfg['alias'];
$action = $_GET['action'] ?? 'list'; $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];

/* ---------- helpers ---------- */
function module_select_sql(string $m): string {
    return match($m) {
      'farmers' => "SELECT f.* FROM farmers f",
      'farms' => "SELECT f.*, CONCAT(fr.first_name,' ',fr.last_name) AS farmer_name, st.soil_type_name, it.irrigation_type_name FROM farms f JOIN farmers fr USING(farmer_id) LEFT JOIN soil_types st USING(soil_type_id) LEFT JOIN irrigation_types it USING(irrigation_type_id)",
      'crops' => "SELECT c.*, cc.category_name, s.season_name FROM crops c JOIN crop_categories cc USING(category_id) LEFT JOIN seasons s ON s.season_id=c.recommended_season_id",
      'plantings' => "SELECT p.*, f.farm_name, c.crop_name, ps.status_name FROM plantings p JOIN farms f USING(farm_id) JOIN crops c USING(crop_id) JOIN planting_statuses ps USING(status_id)",
      'harvests' => "SELECT h.*, c.crop_name, f.farm_name, qg.grade_name, hr.remark_description FROM harvests h JOIN plantings p USING(planting_id) JOIN farms f USING(farm_id) JOIN crops c USING(crop_id) LEFT JOIN quality_grades qg USING(grade_id) LEFT JOIN harvest_remarks hr USING(remark_id)",
      default => throw new RuntimeException('Unknown module')
    };
}
function module_options(PDO $pdo, array $field): array {
    [,,,$t,$k,$l] = $field;
    return $pdo->query("SELECT {$k} AS id, {$l} AS label FROM {$t} ORDER BY label")->fetchAll();
}
function module_filter_options(PDO $pdo, array $f): array {
    if ($f['type'] === 'lookup') {
        return $pdo->query("SELECT {$f['key']} AS id, {$f['text']} AS label FROM {$f['table']} ORDER BY label")->fetchAll();
    }
    return $pdo->query("SELECT DISTINCT {$f['text']} AS id, {$f['text']} AS label FROM {$f['table']} WHERE {$f['text']} IS NOT NULL ORDER BY label")->fetchAll();
}
function module_url(array $over = []): string {
    $p = array_merge($_GET, $over);
    foreach ($p as $k => $v) if ($v === null || $v === '') unset($p[$k]);
    return 'module.php?' . http_build_query($p);
}
function module_is_id(string $key): bool {
    return str_ends_with($key, '_id');
}
function module_is_numeric(string $key): bool {
    return in_array($key, ['area_hectares','area_planted','seed_quantity_kg','quantity_kg','selling_price_per_kg','growing_period_days'], true);
}
function module_is_date(string $key): bool {
    return $key === 'date_registered' || str_ends_with($key, '_date');
}
function db_error_message(PDOException $ex): string {
    error_log('[AgriTrack] ' . $ex->getMessage());   // technical detail goes to the server log only
    return match((string)$ex->getCode()) {
        '23505' => 'A record with the same values already exists.',
        '23503' => 'A selected related record does not exist.',
        '23514' => 'One of the values is outside the allowed range.',
        '23502' => 'A required field is missing.',
        '22001' => 'One of the values is too long.',
        '22003' => 'A number is too large.',
        default => 'The record could not be saved. Please check your entries and try again.'
    };
}
function module_validate(PDO $pdo, array $cfg, array $in, array &$errors): array {
    $data = [];
    foreach ($cfg['fields'] as $name => $field) {
        [$label, $type, $required] = $field;
        $o = $type === 'lookup' ? [] : ($field[3] ?? []);
        $v = trim((string)($in[$name] ?? ''));
        $data[$name] = $v === '' ? null : $v;
        if ($v === '') { if ($required) $errors[] = "$label is required."; continue; }
        if ($type === 'text' || $type === 'textarea') {
            if (isset($o['max']) && mb_strlen($v) > $o['max']) $errors[] = "$label must not exceed {$o['max']} characters.";
            if (isset($o['pattern']) && !preg_match($o['pattern'], $v)) $errors[] = $o['pattern_msg'] ?? "$label has an invalid format.";
        } elseif ($type === 'number') {
            $isInt = !empty($o['int']);
            if (!preg_match($isInt ? '/^\d+$/' : '/^\d+(\.\d{1,2})?$/', $v)) {
                $errors[] = $label . ($isInt ? ' must be a whole number.' : ' must be a valid non-negative number with up to 2 decimal places.');
                continue;
            }
            $n = (float)$v;
            if (isset($o['min']) && $n < $o['min']) $errors[] = "$label must be at least {$o['min']}.";
            if (isset($o['max']) && $n > $o['max']) $errors[] = "$label must not exceed {$o['max']}.";
        } elseif ($type === 'date') {
            if (!validate_date($v)) { $errors[] = "$label must be a valid date."; continue; }
            if (!empty($o['not_future']) && $v > date('Y-m-d')) $errors[] = "$label cannot be in the future.";
        } elseif ($type === 'lookup') {
            [, , , $t, $k] = $field;
            if (!ctype_digit($v)) { $errors[] = "Please choose a valid $label."; continue; }
            $st = $pdo->prepare("SELECT 1 FROM {$t} WHERE {$k}=:v LIMIT 1"); $st->execute(['v' => (int)$v]);
            if (!$st->fetchColumn()) $errors[] = "The selected $label does not exist.";
        }
    }
    return $data;
}
function module_cross_checks(PDO $pdo, string $m, array $d, array &$errors): void {
    if ($errors) return;
    if ($m === 'plantings') {
        if ($d['expected_harvest_date'] < $d['planting_date']) $errors[] = 'Expected harvest date cannot be earlier than the planting date.';
        $st = $pdo->prepare('SELECT area_hectares FROM farms WHERE farm_id=:i'); $st->execute(['i' => (int)$d['farm_id']]);
        $area = $st->fetchColumn();
        if ($area !== false && (float)$d['area_planted'] > (float)$area) $errors[] = 'Area planted cannot exceed the farm\'s total area (' . number_format((float)$area, 2) . ' ha).';
    }
    if ($m === 'harvests') {
        $st = $pdo->prepare('SELECT planting_date FROM plantings WHERE planting_id=:i'); $st->execute(['i' => (int)$d['planting_id']]);
        $pd = $st->fetchColumn();
        if ($pd && $d['harvest_date'] < $pd) $errors[] = 'Harvest date cannot be earlier than the planting date (' . $pd . ').';
    }
}

/* ---------- POST: create / update / delete ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $postAction = $_POST['form_action'] ?? '';
    if ($postAction === 'delete') {
        if (!can_delete()) { http_response_code(403); exit('Administrator permission required.'); }
        $delId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        try { $st = $pdo->prepare("DELETE FROM {$table} WHERE {$pk}=:id"); $st->execute(['id' => $delId]); flash('success', 'Record deleted successfully.'); }
        catch (PDOException $ex) {
            error_log('[AgriTrack] ' . $ex->getMessage());
            flash('danger', 'This record cannot be deleted because other records depend on it.');
        }
        redirect("module.php?m={$m}");
    }
    $data = module_validate($pdo, $cfg, $_POST, $errors);
    module_cross_checks($pdo, $m, $data, $errors);
    if (!$errors) {
        try {
            if ($postAction === 'update') {
                $sets = implode(',', array_map(fn($k) => "$k=:$k", array_keys($data)));
                $data['id'] = (int)($_POST['id'] ?? 0);
                $st = $pdo->prepare("UPDATE {$table} SET {$sets} WHERE {$pk}=:id"); $st->execute($data);
                flash('success', 'Record updated successfully.');
            } else {
                $cols = implode(',', array_keys($data)); $params = implode(',', array_map(fn($k) => ":$k", array_keys($data)));
                $st = $pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$params})"); $st->execute($data);
                flash('success', 'Record created successfully.');
            }
            redirect("module.php?m={$m}");
        } catch (PDOException $ex) { $errors[] = db_error_message($ex); }
    }
    $action = $postAction === 'update' ? 'edit' : 'create';
    $id = (int)($_POST['id'] ?? 0);
}

/* ---------- VIEW (read-only detail page) ---------- */
if ($action === 'view') {
    $st = $pdo->prepare("SELECT * FROM {$table} WHERE {$pk}=:id"); $st->execute(['id' => $id]); $rec = $st->fetch();
    if (!$rec) { flash('warning', 'Record not found.'); redirect("module.php?m={$m}"); }
    $pageTitle = 'View ' . rtrim($cfg['title'], 's');
    require __DIR__ . '/includes/header.php';
    ?>
    <a href="module.php?m=<?= e($m) ?>" class="back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to <?= e($cfg['title']) ?></a>
    <section class="panel form-panel">
      <div class="panel-head">
        <div>
          <h2 class="section-title">Record details <span class="record-chip">#<?= (int)$rec[$pk] ?></span></h2>
          <div class="panel-sub">Read-only details for this <?= e(strtolower(rtrim($cfg['title'], 's'))) ?>.</div>
        </div>
        <a class="btn btn-outline-secondary btn-sm" href="module.php?m=<?= e($m) ?>&action=edit&id=<?= (int)$rec[$pk] ?>"><i class="bi bi-pencil me-1" aria-hidden="true"></i>Edit record</a>
      </div>
      <div class="table-responsive"><table class="table detail-table"><caption class="visually-hidden"><?= e($cfg['title']) ?> record #<?= (int)$rec[$pk] ?></caption><tbody>
    <?php foreach ($cfg['fields'] as $name => $field):
        [$label, $type] = $field; $val = $rec[$name] ?? null;
        if ($type === 'lookup' && $val !== null && $val !== '') {
            [, , , $t, $k, $l] = $field;
            $q2 = $pdo->prepare("SELECT {$l} AS label FROM {$t} WHERE {$k}=:v LIMIT 1"); $q2->execute(['v' => $val]); $val = $q2->fetchColumn();
        } elseif ($name === 'selling_price_per_kg' && $val !== null) { $val = '₱' . number_format((float)$val, 2); }
        elseif (in_array($name, ['quantity_kg','area_hectares','area_planted','seed_quantity_kg'], true) && $val !== null) { $val = number_format((float)$val, 2); }
        elseif (module_is_date($name) && $val !== null) { $val = nice_date((string)$val); }
    ?>
    <tr><th scope="row"><?= e($label) ?></th><td><?= e(($val === null || $val === '') ? '—' : $val) ?></td></tr>
    <?php endforeach; ?>
    <tr><th scope="row">Created</th><td><?= e(!empty($rec['created_at']) ? date('j M Y, H:i', strtotime($rec['created_at'])) : '—') ?></td></tr>
    <tr><th scope="row">Last updated</th><td><?= e(!empty($rec['updated_at']) ? date('j M Y, H:i', strtotime($rec['updated_at'])) : '—') ?></td></tr>
    </tbody></table></div>
    </section>
    <?php require __DIR__ . '/includes/footer.php'; exit;
}

/* ---------- CREATE / EDIT form ---------- */
$record = [];
if (in_array($action, ['create', 'edit'], true)) {
    if ($action === 'edit' && $id) {
        $st = $pdo->prepare("SELECT * FROM {$table} WHERE {$pk}=:id"); $st->execute(['id' => $id]); $record = $st->fetch() ?: [];
        if (!$record) { flash('warning', 'Record not found.'); redirect("module.php?m={$m}"); }
    }
    foreach ($cfg['fields'] as $name => $field) if (isset($_POST[$name])) $record[$name] = $_POST[$name];
    if ($action === 'create' && $m === 'farmers' && !isset($record['date_registered'])) $record['date_registered'] = date('Y-m-d');
    $pageTitle = ($action === 'create' ? 'Add ' : 'Edit ') . rtrim($cfg['title'], 's');
    require __DIR__ . '/includes/header.php';
    ?>
    <a href="module.php?m=<?= e($m) ?>" class="back-link"><i class="bi bi-arrow-left" aria-hidden="true"></i> Back to <?= e($cfg['title']) ?></a>
    <section class="panel form-panel">
      <div class="panel-head">
        <div><h2 class="section-title"><?= e($pageTitle) ?></h2><div class="panel-sub">Enter the record details below. Fields marked * are required.</div></div>
      </div>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger" role="alert"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="row g-3 form-grid"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form_action" value="<?= e($action === 'edit' ? 'update' : 'create') ?>"><input type="hidden" name="id" value="<?= (int)$id ?>">
    <?php foreach ($cfg['fields'] as $name => $field): [$label, $type, $required] = $field; $o = $type === 'lookup' ? [] : ($field[3] ?? []); $fid = 'field_' . $name; ?>
    <div class="<?= $type === 'textarea' ? 'col-12' : 'col-md-6' ?>"><label class="form-label <?= $required ? 'required' : '' ?>" for="<?= e($fid) ?>"><?= e($label) ?></label>
    <?php if ($type === 'lookup'): ?><select class="form-select" id="<?= e($fid) ?>" name="<?= e($name) ?>" <?= $required ? 'required' : '' ?>><option value="">Choose <?= e(strtolower($label)) ?></option><?php foreach (module_options($pdo, $field) as $opt): ?><option value="<?= e($opt['id']) ?>" <?= (string)($record[$name] ?? '') === (string)$opt['id'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option><?php endforeach; ?></select>
    <?php elseif ($type === 'textarea'): ?><textarea class="form-control" id="<?= e($fid) ?>" rows="3" name="<?= e($name) ?>" <?= isset($o['max']) ? 'maxlength="' . (int)$o['max'] . '"' : '' ?> <?= $required ? 'required' : '' ?>><?= e($record[$name] ?? '') ?></textarea>
    <?php elseif ($type === 'number'): ?><input class="form-control" id="<?= e($fid) ?>" type="number" name="<?= e($name) ?>" value="<?= e($record[$name] ?? '') ?>" min="<?= e($o['min'] ?? 0) ?>" <?= isset($o['max']) ? 'max="' . e($o['max']) . '"' : '' ?> step="<?= !empty($o['int']) ? '1' : '0.01' ?>" inputmode="decimal" <?= $required ? 'required' : '' ?>>
    <?php else: ?><input class="form-control" id="<?= e($fid) ?>" type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($record[$name] ?? '') ?>" <?= isset($o['max']) && $type === 'text' ? 'maxlength="' . (int)$o['max'] . '"' : '' ?> <?= $type === 'date' && !empty($o['not_future']) ? 'max="' . date('Y-m-d') . '"' : '' ?> <?= $required ? 'required' : '' ?>><?php endif; ?></div>
    <?php endforeach; ?>
    <div class="col-12 form-actions"><button class="btn btn-primary"><i class="bi bi-check2 me-1" aria-hidden="true"></i>Save record</button><a class="btn btn-outline-secondary" href="module.php?m=<?= e($m) ?>">Cancel</a></div></form></section>
    <?php require __DIR__ . '/includes/footer.php'; exit;
}

/* ---------- LIST: search + filters + sorting + pagination ---------- */
$pageTitle = $cfg['title'];
$q = trim($_GET['q'] ?? ''); $page = max(1, (int)($_GET['page'] ?? 1)); $perPage = 10; $offset = ($page - 1) * $perPage;
$base = module_select_sql($m); $conds = []; $params = []; $activeFilters = [];

if ($q !== '') {
    $parts = [];
    foreach ($cfg['search'] as $i => $col) { $parts[] = "CAST({$col} AS TEXT) ILIKE :q{$i}"; $params["q{$i}"] = '%' . $q . '%'; }
    $conds[] = '(' . implode(' OR ', $parts) . ')';
}
foreach ($cfg['filters'] as $fk => $f) {
    $val = trim((string)($_GET["f_{$fk}"] ?? ''));
    if ($val === '') continue;
    if ($f['type'] === 'lookup') { $iv = filter_var($val, FILTER_VALIDATE_INT); if ($iv === false) continue; $params["f_{$fk}"] = $iv; }
    else { $params["f_{$fk}"] = $val; }
    $conds[] = "{$f['col']} = :f_{$fk}"; $activeFilters[$fk] = $val;
}
$where = $conds ? ' WHERE ' . implode(' AND ', $conds) : '';

$sort = $_GET['sort'] ?? ''; if (!isset($cfg['columns'][$sort])) $sort = '';
$dir = (($_GET['dir'] ?? '') === 'asc') ? 'ASC' : 'DESC';
$orderBy = $sort !== ''
    ? ($cfg['sortmap'][$sort] ?? "{$alias}.{$sort}") . " {$dir}, {$alias}.{$pk} DESC"
    : "{$alias}.{$pk} DESC";

$st = $pdo->prepare("SELECT COUNT(*) FROM (" . $base . $where . ") AS filtered_records"); $st->execute($params);
$total = (int)$st->fetchColumn(); $pages = max(1, (int)ceil($total / $perPage)); if ($page > $pages) { $page = $pages; $offset = ($page - 1) * $perPage; }
$st = $pdo->prepare($base . $where . " ORDER BY {$orderBy} LIMIT {$perPage} OFFSET {$offset}"); $st->execute($params); $rows = $st->fetchAll();
$hasFilters = $q !== '' || $activeFilters;
$singular = strtolower(rtrim($cfg['title'], 's'));
$shownFrom = $total ? $offset + 1 : 0;
$shownTo = min($offset + $perPage, $total);
require __DIR__ . '/includes/header.php';
?>
<section class="panel records-panel">
  <div class="records-head">
    <div>
      <h2 class="section-title">All <?= e(lcfirst($cfg['title'])) ?></h2>
      <div class="meta"><strong><?= number_format($total) ?></strong> record<?= $total === 1 ? '' : 's' ?><?= $hasFilters ? ' matching the current filters' : '' ?><?php if ($pages > 1): ?> · Page <?= $page ?> of <?= $pages ?><?php endif; ?></div>
    </div>
    <a class="btn btn-primary" href="module.php?m=<?= e($m) ?>&action=create"><i class="bi bi-plus-lg me-1" aria-hidden="true"></i>Add <?= e($singular) ?></a>
  </div>

  <form class="filter-bar" method="get" aria-label="Search and filter records">
    <input type="hidden" name="m" value="<?= e($m) ?>"><?php if ($sort !== ''): ?><input type="hidden" name="sort" value="<?= e($sort) ?>"><input type="hidden" name="dir" value="<?= e(strtolower($dir)) ?>"><?php endif; ?>
    <div class="filter-field grow"><label class="form-label" for="filter_q">Search</label><input class="form-control form-control-sm" id="filter_q" type="search" name="q" value="<?= e($q) ?>" placeholder="Search <?= e(strtolower($cfg['title'])) ?>…"></div>
    <?php foreach ($cfg['filters'] as $fk => $f): ?><div class="filter-field"><label class="form-label" for="f_<?= e($fk) ?>"><?= e($f['label']) ?></label><select class="form-select form-select-sm" id="f_<?= e($fk) ?>" name="f_<?= e($fk) ?>"><option value="">All</option><?php foreach (module_filter_options($pdo, $f) as $opt): ?><option value="<?= e($opt['id']) ?>" <?= (string)($activeFilters[$fk] ?? '') === (string)$opt['id'] ? 'selected' : '' ?>><?= e($opt['label']) ?></option><?php endforeach; ?></select></div>
    <?php endforeach; ?>
    <div class="filter-actions"><button class="btn btn-outline-secondary btn-sm"><i class="bi bi-funnel me-1" aria-hidden="true"></i>Apply</button><?php if ($hasFilters || $sort !== ''): ?><a class="btn btn-outline-secondary btn-sm" href="module.php?m=<?= e($m) ?>">Clear</a><?php endif; ?></div>
  </form>

  <div class="table-responsive"><table class="table">
    <caption class="visually-hidden"><?= e($cfg['title']) ?> records</caption>
    <thead><tr>
    <?php foreach ($cfg['columns'] as $key => $label): $isSorted = ($sort === $key); $next = ($isSorted && $dir === 'ASC') ? 'desc' : 'asc'; $numeric = module_is_numeric($key); ?>
    <th scope="col" class="<?= $numeric ? 'num' : '' ?>"<?= $isSorted ? ' aria-sort="' . ($dir === 'ASC' ? 'ascending' : 'descending') . '"' : '' ?>><a href="<?= e(module_url(['sort' => $key, 'dir' => $next, 'page' => null])) ?>"><?= e($label) ?><?php if ($isSorted): ?> <i class="bi bi-caret-<?= $dir === 'ASC' ? 'up' : 'down' ?>-fill" aria-hidden="true"></i><?php endif; ?></a></th>
    <?php endforeach; ?><th scope="col" class="actions-cell">Actions</th></tr></thead><tbody>
    <?php foreach ($rows as $row): ?><tr><?php foreach ($cfg['columns'] as $key => $label): $numeric = module_is_numeric($key); $isId = module_is_id($key); ?>
      <td class="<?= $numeric ? 'num' : ($isId ? 'id-col' : '') ?>"><?php if ($key === 'status_name'): ?><span class="badge-soft <?= e(status_tone((string)($row[$key] ?? ''))) ?>"><?= e($row[$key] ?? '') ?></span><?php elseif ($isId): ?>#<?= (int)$row[$key] ?><?php elseif ($key === 'quantity_kg'): ?><?= number_format((float)$row[$key], 2) ?><?php elseif ($key === 'selling_price_per_kg'): ?>₱<?= number_format((float)$row[$key], 2) ?><?php elseif ($key === 'area_hectares' || $key === 'area_planted'): ?><?= number_format((float)$row[$key], 2) ?><?php elseif (module_is_date($key)): ?><?= e(nice_date((string)($row[$key] ?? ''))) ?><?php else: ?><?= e(($row[$key] === null || $row[$key] === '') ? '—' : $row[$key]) ?><?php endif; ?></td>
    <?php endforeach; ?>
      <td class="actions-cell"><span class="row-actions">
        <a class="btn btn-sm btn-outline-secondary btn-icon" href="module.php?m=<?= e($m) ?>&action=view&id=<?= (int)$row[$pk] ?>" aria-label="View record #<?= (int)$row[$pk] ?>" title="View"><i class="bi bi-eye" aria-hidden="true"></i></a>
        <a class="btn btn-sm btn-outline-secondary btn-icon" href="module.php?m=<?= e($m) ?>&action=edit&id=<?= (int)$row[$pk] ?>" aria-label="Edit record #<?= (int)$row[$pk] ?>" title="Edit"><i class="bi bi-pencil" aria-hidden="true"></i></a>
        <?php if (can_delete()): ?><form method="post" class="d-inline" onsubmit="return confirm('Permanently delete this record? Records that other data depends on cannot be deleted.');"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= (int)$row[$pk] ?>"><button class="btn btn-sm btn-outline-danger btn-icon" aria-label="Delete record #<?= (int)$row[$pk] ?>" title="Delete"><i class="bi bi-trash" aria-hidden="true"></i></button></form><?php endif; ?>
      </span></td>
    </tr><?php endforeach; ?>
    <?php if (!$rows): ?><tr><td colspan="<?= count($cfg['columns']) + 1 ?>"><div class="empty-state"><span class="empty-icon"><i class="bi bi-inbox" aria-hidden="true"></i></span><strong>No records found</strong><p><?= $hasFilters ? 'No ' . e($cfg['title']) . ' match the current search or filters. Try a broader search.' : 'Add the first ' . e($singular) . ' to start building this list.' ?></p><?php if ($hasFilters): ?><a class="btn btn-outline-secondary btn-sm" href="module.php?m=<?= e($m) ?>">Clear search and filters</a><?php else: ?><a class="btn btn-primary btn-sm" href="module.php?m=<?= e($m) ?>&action=create">Add <?= e($singular) ?></a><?php endif; ?></div></td></tr><?php endif; ?>
  </tbody></table></div>

  <?php if ($pages > 1):
      $show = array_unique(array_filter(array_merge([1, $pages], range(max(1, $page - 2), min($pages, $page + 2))), fn($p) => $p >= 1 && $p <= $pages)); sort($show); $prev = 0; ?>
  <div class="table-foot">
    <span class="meta">Showing <strong><?= number_format($shownFrom) ?>–<?= number_format($shownTo) ?></strong> of <?= number_format($total) ?></span>
    <nav aria-label="Pagination"><ul class="pagination pagination-sm">
      <li class="page-item <?= $page <= 1 ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(module_url(['page' => max(1, $page - 1)])) ?>" aria-label="Previous page"><i class="bi bi-chevron-left" aria-hidden="true"></i></a></li>
      <?php foreach ($show as $p): if ($prev && $p - $prev > 1): ?><li class="page-item disabled"><span class="page-link">…</span></li><?php endif; $prev = $p; ?>
      <li class="page-item <?= $p === $page ? 'active' : '' ?>"><a class="page-link" href="<?= e(module_url(['page' => $p])) ?>"<?= $p === $page ? ' aria-current="page"' : '' ?>><?= $p ?></a></li>
      <?php endforeach; ?>
      <li class="page-item <?= $page >= $pages ? 'disabled' : '' ?>"><a class="page-link" href="<?= e(module_url(['page' => min($pages, $page + 1)])) ?>" aria-label="Next page"><i class="bi bi-chevron-right" aria-hidden="true"></i></a></li>
    </ul></nav>
  </div>
  <?php endif; ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
