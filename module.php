<?php
require_once __DIR__ . '/config/app.php';
require_login();
$all = require __DIR__ . '/modules.php';
$m = $_GET['m'] ?? 'farmers';
if (!isset($all[$m])) { http_response_code(404); exit('Module not found.'); }
$cfg = $all[$m]; $pdo = db(); $pk = $cfg['pk']; $table = $cfg['table'];
$action = $_GET['action'] ?? 'list'; $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$errors = [];

function module_select_sql(string $m): string {
    return match($m) {
      'farmers' => "SELECT f.* FROM farmers f",
      'farms' => "SELECT f.*, CONCAT(fr.first_name,' ',fr.last_name) AS farmer_name, st.soil_type_name, it.irrigation_type_name FROM farms f JOIN farmers fr USING(farmer_id) LEFT JOIN soil_types st USING(soil_type_id) LEFT JOIN irrigation_types it USING(irrigation_type_id)",
      'crops' => "SELECT c.*, cc.category_name, s.season_name FROM crops c JOIN crop_categories cc USING(category_id) LEFT JOIN seasons s USING(recommended_season_id)",
      'plantings' => "SELECT p.*, f.farm_name, c.crop_name, ps.status_name FROM plantings p JOIN farms f USING(farm_id) JOIN crops c USING(crop_id) JOIN planting_statuses ps USING(status_id)",
      'harvests' => "SELECT h.*, c.crop_name, f.farm_name, qg.grade_name, hr.remark_description FROM harvests h JOIN plantings p USING(planting_id) JOIN farms f USING(farm_id) JOIN crops c USING(crop_id) LEFT JOIN quality_grades qg USING(grade_id) LEFT JOIN harvest_remarks hr USING(remark_id)",
      default => throw new RuntimeException('Unknown module')
    };
}
function module_options(PDO $pdo, array $field): array {
    [,,$required,$table,$key,$label] = $field;
    return $pdo->query("SELECT {$key} AS id, {$label} AS label FROM {$table} ORDER BY label")->fetchAll();
}
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $postAction = $_POST['form_action'] ?? '';
    if ($postAction === 'delete') {
        if (!can_delete()) { http_response_code(403); exit('Administrator permission required.'); }
        $delId = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT);
        try { $st=$pdo->prepare("DELETE FROM {$table} WHERE {$pk}=:id"); $st->execute(['id'=>$delId]); flash('success','Record deleted successfully.'); }
        catch (PDOException $ex) { flash('danger','This record cannot be deleted because other records depend on it.'); }
        redirect("module.php?m={$m}");
    }
    $data=[];
    foreach ($cfg['fields'] as $name=>$field) {
        $value = trim((string)($_POST[$name] ?? ''));
        if ($field[2] && $value === '') $errors[] = $field[0] . ' is required.';
        if ($field[1] === 'number' && $value !== '' && (!is_numeric($value) || (float)$value < 0)) $errors[] = $field[0] . ' must be a valid non-negative number.';
        if ($field[1] === 'date' && $value !== '' && !validate_date($value)) $errors[] = $field[0] . ' must be a valid date.';
        $data[$name] = $value === '' ? null : $value;
    }
    if ($m==='plantings' && !empty($data['planting_date']) && !empty($data['expected_harvest_date']) && $data['expected_harvest_date'] < $data['planting_date']) $errors[]='Expected harvest date cannot be earlier than planting date.';
    if (!$errors) {
        try {
            if ($postAction === 'update') {
                $sets = implode(',', array_map(fn($k)=>"$k=:$k",array_keys($data)));
                $data['id']=(int)($_POST['id'] ?? 0);
                $st=$pdo->prepare("UPDATE {$table} SET {$sets} WHERE {$pk}=:id"); $st->execute($data);
                flash('success','Record updated successfully.');
            } else {
                $cols=implode(',',array_keys($data)); $params=implode(',',array_map(fn($k)=>":$k",array_keys($data)));
                $st=$pdo->prepare("INSERT INTO {$table} ({$cols}) VALUES ({$params})"); $st->execute($data);
                flash('success','Record created successfully.');
            }
            redirect("module.php?m={$m}");
        } catch (PDOException $ex) { $errors[]='The record could not be saved. Check for duplicate values and valid related records.'; }
    }
    $action = $postAction === 'update' ? 'edit' : 'create';
    $id = (int)($_POST['id'] ?? 0);
}
$record=[];
if (in_array($action,['create','edit'],true)) {
    if ($action==='edit' && $id) {
        $st=$pdo->prepare("SELECT * FROM {$table} WHERE {$pk}=:id"); $st->execute(['id'=>$id]); $record=$st->fetch() ?: [];
        if (!$record) { flash('warning','Record not found.'); redirect("module.php?m={$m}"); }
    }
    foreach ($cfg['fields'] as $name=>$field) if (isset($_POST[$name])) $record[$name]=$_POST[$name];
    $pageTitle=($action==='create'?'Add ':'Edit ').rtrim($cfg['title'],'s');
    require __DIR__.'/includes/header.php';
    ?>
    <div class="mb-3"><a href="module.php?m=<?= e($m) ?>" class="text-success text-decoration-none small"><i class="bi bi-arrow-left"></i> Back to <?= e($cfg['title']) ?></a></div>
    <section class="panel form-panel"><div class="panel-head"><div><div class="section-title"><?= e($pageTitle) ?></div><div class="muted small">Enter the record details below. Fields marked * are required.</div></div></div>
    <?php foreach($errors as $err): ?><div class="alert alert-danger py-2 small"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post" class="row g-3"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form_action" value="<?= e($action==='edit'?'update':'create') ?>"><input type="hidden" name="id" value="<?= (int)$id ?>">
    <?php foreach($cfg['fields'] as $name=>$field): [$label,$type,$required]=$field; ?>
    <div class="<?= $type==='textarea'?'col-12':'col-md-6' ?>"><label class="form-label <?= $required?'required':'' ?>"><?= e($label) ?></label>
    <?php if($type==='lookup'): ?><select class="form-select" name="<?= e($name) ?>" <?= $required?'required':'' ?>><option value="">Choose <?= e(strtolower($label)) ?></option><?php foreach(module_options($pdo,$field) as $opt): ?><option value="<?= e($opt['id']) ?>" <?= (string)($record[$name]??'')===(string)$opt['id']?'selected':'' ?>><?= e($opt['label']) ?></option><?php endforeach; ?></select>
    <?php elseif($type==='textarea'): ?><textarea class="form-control" rows="3" name="<?= e($name) ?>" <?= $required?'required':'' ?>><?= e($record[$name]??'') ?></textarea>
    <?php else: ?><input class="form-control" type="<?= e($type) ?>" name="<?= e($name) ?>" value="<?= e($record[$name]??'') ?>" <?= $required?'required':'' ?> <?= $type==='number'?'min="0" step="0.01"':'' ?>><?php endif; ?></div>
    <?php endforeach; ?>
    <div class="col-12 d-flex gap-2 pt-2"><button class="btn btn-primary"><i class="bi bi-check2 me-1"></i> Save record</button><a class="btn btn-outline-secondary" href="module.php?m=<?= e($m) ?>">Cancel</a></div></form></section>
    <?php require __DIR__.'/includes/footer.php'; exit;
}
$pageTitle=$cfg['title'];
$q=trim($_GET['q']??''); $page=max(1,(int)($_GET['page']??1)); $perPage=10; $offset=($page-1)*$perPage;
$base=module_select_sql($m); $where=''; $params=[];
if ($q!=='') { $parts=[]; foreach($cfg['search'] as $i=>$col) { $parts[]="CAST({$col} AS TEXT) ILIKE :q{$i}"; $params["q{$i}"]='%'.$q.'%'; } $where=' WHERE '.implode(' OR ',$parts); }
$countSql="SELECT COUNT(*) FROM {$table}"; // filtered count uses the module's joined view below
$countSql="SELECT COUNT(*) FROM (".$base.$where.") AS filtered_records";
$st=$pdo->prepare($countSql); $st->execute($params); $total=(int)$st->fetchColumn(); $pages=max(1,(int)ceil($total/$perPage));
$listSql=$base.$where." ORDER BY {$pk} DESC LIMIT {$perPage} OFFSET {$offset}";
$st=$pdo->prepare($listSql); $st->execute($params); $rows=$st->fetchAll();
require __DIR__.'/includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3"><div class="muted small"><?= number_format($total) ?> total record<?= $total===1?'':'s' ?> · Page <?= $page ?> of <?= $pages ?></div><div class="page-tools"><form class="d-flex gap-2" method="get"><input type="hidden" name="m" value="<?= e($m) ?>"><input class="form-control search-wrap" name="q" value="<?= e($q) ?>" placeholder="Search <?= e(strtolower($cfg['title'])) ?>..."><button class="btn btn-outline-secondary btn-sm"><i class="bi bi-search"></i></button><?php if($q!==''): ?><a class="btn btn-outline-secondary btn-sm" href="module.php?m=<?= e($m) ?>">Clear</a><?php endif; ?></form><a class="btn btn-primary" href="module.php?m=<?= e($m) ?>&action=create"><i class="bi bi-plus-lg me-1"></i> Add record</a></div></div>
<section class="panel"><div class="table-responsive"><table class="table"><thead><tr><?php foreach($cfg['columns'] as $label): ?><th><?= e($label) ?></th><?php endforeach; ?><th class="text-end">Actions</th></tr></thead><tbody>
<?php foreach($rows as $row): ?><tr><?php foreach($cfg['columns'] as $key=>$label): ?><td><?php if($key==='status_name'): ?><span class="badge-soft"><?= e($row[$key]??'') ?></span><?php elseif($key==='quantity_kg'): ?><?= number_format((float)$row[$key],2) ?><?php elseif($key==='selling_price_per_kg'): ?>₱<?= number_format((float)$row[$key],2) ?><?php elseif($key==='area_hectares'||$key==='area_planted'): ?><?= number_format((float)$row[$key],2) ?><?php else: ?><?= e($row[$key]??'—') ?><?php endif; ?></td><?php endforeach; ?><td class="text-end text-nowrap"><a class="btn btn-sm btn-outline-secondary" href="module.php?m=<?= e($m) ?>&action=edit&id=<?= (int)$row[$pk] ?>" title="Edit"><i class="bi bi-pencil"></i></a><?php if(can_delete()): ?><form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="form_action" value="delete"><input type="hidden" name="id" value="<?= (int)$row[$pk] ?>"><button class="btn btn-sm btn-outline-danger" data-confirm="Permanently delete this record? Related records may prevent deletion." title="Delete"><i class="bi bi-trash"></i></button></form><?php endif; ?></td></tr><?php endforeach; ?>
<?php if(!$rows): ?><tr><td colspan="<?= count($cfg['columns'])+1 ?>"><div class="empty-state"><i class="bi bi-inbox"></i>No records found<?= $q!==''?' for this search':'' ?>.</div></td></tr><?php endif; ?>
</tbody></table></div>
<?php if($pages>1): ?><nav class="mt-3"><ul class="pagination pagination-sm justify-content-end mb-0"><?php for($p=1;$p<=$pages;$p++): ?><li class="page-item <?= $p===$page?'active':'' ?>"><a class="page-link" href="?m=<?= e($m) ?>&q=<?= urlencode($q) ?>&page=<?= $p ?>"><?= $p ?></a></li><?php endfor; ?></ul></nav><?php endif; ?>
</section>
<?php require __DIR__.'/includes/footer.php'; ?>
