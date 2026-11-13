<?php
require_once __DIR__ . '/config/app.php';
require_login();
if ((current_user()['role'] ?? '') !== 'Administrator') { http_response_code(403); exit('Administrator permission required.'); }
$pdo = db(); $pageTitle = 'User accounts'; $errors = []; $me = (int)(current_user()['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $do = $_POST['do'] ?? '';
    try {
        if ($do === 'create') {
            $name = trim($_POST['full_name'] ?? ''); $email = strtolower(trim($_POST['email'] ?? ''));
            $pass = (string)($_POST['password'] ?? ''); $role = $_POST['role'] ?? '';
            if ($name === '' || mb_strlen($name) > 120) $errors[] = 'Full name is required (maximum 120 characters).';
            if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 180) $errors[] = 'Enter a valid email address.';
            if (strlen($pass) < 10) $errors[] = 'Password must be at least 10 characters.';
            if (!in_array($role, ['Administrator', 'Staff'], true)) $errors[] = 'Choose a valid role.';
            if (!$errors) {
                $st = $pdo->prepare('INSERT INTO app_users(full_name,email,password_hash,role) VALUES(:n,:e,:p,:r)');
                $st->execute(['n' => $name, 'e' => $email, 'p' => password_hash($pass, PASSWORD_DEFAULT), 'r' => $role]);
                flash('success', 'User account created successfully.'); redirect('users.php');
            }
        } elseif ($do === 'toggle') {
            $uid = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT);
            if ($uid === $me) { flash('warning', 'You cannot deactivate your own account.'); }
            else { $pdo->prepare('UPDATE app_users SET is_active = NOT is_active WHERE user_id=:i')->execute(['i' => $uid]); flash('success', 'User status updated.'); }
            redirect('users.php');
        } elseif ($do === 'reset') {
            $uid = filter_var($_POST['user_id'] ?? null, FILTER_VALIDATE_INT); $pass = (string)($_POST['new_password'] ?? '');
            if (strlen($pass) < 10) { flash('danger', 'New password must be at least 10 characters.'); }
            else { $pdo->prepare('UPDATE app_users SET password_hash=:p WHERE user_id=:i')->execute(['p' => password_hash($pass, PASSWORD_DEFAULT), 'i' => $uid]); flash('success', 'Password updated.'); }
            redirect('users.php');
        }
    } catch (PDOException $ex) {
        error_log('[AgriTrack] ' . $ex->getMessage());
        $errors[] = ((string)$ex->getCode() === '23505') ? 'An account with that email already exists.' : 'The account could not be saved. Please try again.';
    }
}
$users = $pdo->query('SELECT user_id, full_name, email, role, is_active, created_at FROM app_users ORDER BY user_id')->fetchAll();
require __DIR__ . '/includes/header.php';
?>
<div class="page-intro">
  <p class="page-lead">Administrator and Staff access to AgriTrack. Accounts sign in with an email address and password.</p>
</div>
<div class="row g-3">
<div class="col-lg-4">
  <section class="panel">
    <div class="panel-head"><div><h2 class="section-title">Add user</h2><div class="panel-sub">Create an Administrator or Staff account.</div></div></div>
    <?php foreach ($errors as $err): ?><div class="alert alert-danger" role="alert"><?= e($err) ?></div><?php endforeach; ?>
    <form method="post">
      <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="do" value="create">
      <label class="form-label required" for="new_full_name">Full name</label><input class="form-control mb-3" id="new_full_name" name="full_name" maxlength="120" required value="<?= e($_POST['full_name'] ?? '') ?>">
      <label class="form-label required" for="new_email">Email</label><input class="form-control mb-3" id="new_email" type="email" name="email" maxlength="180" required value="<?= e($_POST['email'] ?? '') ?>">
      <label class="form-label required" for="new_password">Password (min. 10 characters)</label><input class="form-control mb-3" id="new_password" type="password" name="password" minlength="10" required autocomplete="new-password">
      <label class="form-label required" for="new_role">Role</label><select class="form-select mb-3" id="new_role" name="role"><option value="Staff">Staff (cannot delete records)</option><option value="Administrator">Administrator (full access)</option></select>
      <div class="form-actions"><button class="btn btn-primary w-100"><i class="bi bi-person-plus me-1" aria-hidden="true"></i>Create user</button></div>
    </form>
  </section>
</div>
<div class="col-lg-8">
  <section class="panel">
    <div class="panel-head"><div><h2 class="section-title">Accounts</h2><div class="panel-sub"><?= count($users) ?> user<?= count($users) === 1 ? '' : 's' ?> with access to this workspace</div></div></div>
    <div class="table-responsive"><table class="table">
      <caption class="visually-hidden">User accounts</caption>
      <thead><tr><th>Name</th><th>Role</th><th>Status</th><th class="actions-cell">Actions</th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
      <tr>
        <td><div class="user-cell"><span class="avatar" aria-hidden="true"><?= e(initials((string)$u['full_name'])) ?></span><div><strong><?= e($u['full_name']) ?></strong><div class="sub"><?= e($u['email']) ?></div></div></div></td>
        <td><?= e($u['role']) ?></td>
        <td><span class="<?= $u['is_active'] ? 'badge-soft' : 'badge-muted' ?>"><?= $u['is_active'] ? 'Active' : 'Inactive' ?></span></td>
        <td class="actions-cell">
          <span class="row-actions">
            <form method="post" class="d-inline"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="do" value="toggle"><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>"><button class="btn btn-sm btn-outline-secondary" <?= (int)$u['user_id'] === $me ? 'disabled' : '' ?> aria-label="<?= $u['is_active'] ? 'Deactivate' : 'Activate' ?> account for <?= e($u['full_name']) ?>"><?= $u['is_active'] ? 'Deactivate' : 'Activate' ?></button></form>
            <form method="post" class="reset-form"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><input type="hidden" name="do" value="reset"><input type="hidden" name="user_id" value="<?= (int)$u['user_id'] ?>"><input class="form-control form-control-sm" type="password" name="new_password" minlength="10" placeholder="New password" aria-label="New password for <?= e($u['full_name']) ?>" autocomplete="new-password" required><button class="btn btn-sm btn-outline-secondary" aria-label="Reset password for <?= e($u['full_name']) ?>">Reset</button></form>
          </span>
        </td>
      </tr>
      <?php endforeach; ?>
      </tbody>
    </table></div>
  </section>
</div>
</div>
<?php require __DIR__ . '/includes/footer.php'; ?>
