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
<!doctype html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sign in · AgriTrack</title><link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"><link href="assets/style.css" rel="stylesheet"><link href="assets/login.css" rel="stylesheet"><link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet"></head>
<body class="login-page"><div class="login-card"><div class="login-brand"><span class="brand-mark"><i class="bi bi-flower1"></i></span><div><strong>AgriTrack</strong><small>AGRICULTURE INFORMATION SYSTEM</small></div></div><p class="eyebrow mt-4">WELCOME BACK</p><h1>Sign in to your workspace</h1><p class="muted mb-4">Manage farms, planting activity, and harvest records.</p>
<?php if ($error): ?><div class="alert alert-danger py-2 small"><?= e($error) ?></div><?php endif; ?>
<form method="post"><input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>"><label class="form-label">Email address</label><input class="form-control mb-3" type="email" name="email" required autocomplete="username"><label class="form-label">Password</label><input class="form-control mb-4" type="password" name="password" required autocomplete="current-password"><button class="btn btn-primary w-100">Sign in <i class="bi bi-arrow-right ms-1"></i></button></form><div class="login-foot">Authorized personnel only · Secure access</div></div></body></html>
