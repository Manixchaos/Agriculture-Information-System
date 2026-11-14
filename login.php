<?php
require_once __DIR__ . '/config/app.php';
if (current_user()) redirect('index.php');
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';
    try {
        $stmt = db()->prepare('SELECT user_id, full_name, email, password_hash, role, is_active FROM app_users WHERE lower(email)=:email LIMIT 1');
        $stmt->execute(['email'=>$email]);
        $u = $stmt->fetch();
        if ($u && $u['is_active'] && password_verify($password, $u['password_hash'])) {
            session_regenerate_id(true);
            $_SESSION['user'] = ['id'=>$u['user_id'], 'full_name'=>$u['full_name'], 'email'=>$u['email'], 'role'=>$u['role']];
            redirect('index.php');
        }
        $error = 'The email or password is incorrect.';
    } catch (Throwable $ex) { $error = 'Unable to sign in. Check the database configuration.'; }
}
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Sign in · AgriTrack</title>
<meta name="description" content="Sign in to the AgriTrack agriculture information system.">
<meta name="theme-color" content="#f7f5ed">
<link rel="icon" href="assets/favicon.png" type="image/png" sizes="64x64">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
<link href="assets/login.css" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-frame">
  <header class="login-masthead">
    <a class="login-brand" href="login.php" aria-label="AgriTrack sign in">
      <span class="brand-mark" aria-hidden="true"><img src="assets/logo.png" alt="" width="34" height="34" decoding="async"></span>
      <span class="brand-name">AgriTrack</span>
    </a>
    <p class="login-system">Agriculture information system</p>
  </header>

  <main class="login-main">
    <div class="login-form">
      <h1>Sign in</h1>
      <p class="login-intro">Manage farmers, farms, crops, planting activity, and harvest records in one workspace.</p>
      <?php if ($error): ?>
      <div class="login-error" id="login_error" role="alert"><i class="bi bi-exclamation-circle" aria-hidden="true"></i><span><?= e($error) ?></span></div>
      <?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <div class="login-field<?= $error ? ' has-error' : '' ?>">
          <label for="login_email">Email address</label>
          <input id="login_email" type="email" name="email" required autocomplete="username" inputmode="email" autofocus<?= $error ? ' aria-invalid="true" aria-describedby="login_error"' : '' ?>>
        </div>
        <div class="login-field<?= $error ? ' has-error' : '' ?>">
          <label for="login_password">Password</label>
          <input id="login_password" type="password" name="password" required autocomplete="current-password"<?= $error ? ' aria-invalid="true" aria-describedby="login_error"' : '' ?>>
        </div>
        <button class="login-submit" type="submit">Sign in <i class="bi bi-arrow-right" aria-hidden="true"></i></button>
      </form>
      <p class="login-note"><i class="bi bi-shield-lock" aria-hidden="true"></i>Authorized personnel only · Secure access</p>
    </div>
  </main>

  <footer class="login-footer">
    <p>Good records make better seasons.</p>
    <p>© <?= date('Y') ?> AgriTrack</p>
  </footer>
</div>
</body>
</html>
