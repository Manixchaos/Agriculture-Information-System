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
<link rel="icon" href="assets/favicon.svg" type="image/svg+xml">
<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
<link href="assets/style.css" rel="stylesheet">
<link href="assets/login.css" rel="stylesheet">
</head>
<body class="login-page">
<div class="login-shell">
  <section class="login-visual">
    <svg class="login-art" viewBox="0 0 900 1100" preserveAspectRatio="xMidYMid slice" aria-hidden="true" focusable="false">
      <defs>
        <radialGradient id="sunGlow" cx="50%" cy="50%" r="50%">
          <stop offset="0%" stop-color="#e8c684" stop-opacity=".5"/>
          <stop offset="52%" stop-color="#d9ac61" stop-opacity=".16"/>
          <stop offset="100%" stop-color="#d9ac61" stop-opacity="0"/>
        </radialGradient>
        <linearGradient id="fieldHaze" x1="0" y1="0" x2="0" y2="1">
          <stop offset="0%" stop-color="#0b2115" stop-opacity="0"/>
          <stop offset="100%" stop-color="#0b2115" stop-opacity=".7"/>
        </linearGradient>
      </defs>
      <circle cx="676" cy="250" r="235" fill="url(#sunGlow)"/>
      <circle cx="676" cy="250" r="94" fill="#e3bd76" fill-opacity=".15"/>
      <g fill="none" stroke="#dce8d8" stroke-opacity=".12" stroke-width="1.3">
        <path d="M-60 398 C 132 334, 300 424, 500 380 S 760 302, 960 350"/>
        <path d="M-60 444 C 140 378, 312 470, 512 424 S 768 344, 968 394"/>
        <path d="M-60 490 C 148 422, 320 516, 520 468 S 772 388, 972 438"/>
        <path d="M-60 536 C 154 468, 328 560, 528 512 S 776 430, 976 482"/>
        <path d="M-60 582 C 160 512, 336 604, 536 556 S 780 474, 980 526"/>
        <path d="M-60 628 C 166 556, 344 648, 544 600 S 786 516, 984 570"/>
        <path d="M-60 674 C 172 600, 352 692, 552 644 S 790 558, 988 614"/>
        <path d="M-60 720 C 178 644, 360 736, 560 688 S 796 602, 992 658"/>
        <path d="M-60 766 C 184 688, 368 780, 568 732 S 800 646, 996 702"/>
        <path d="M-60 812 C 190 732, 376 824, 576 776 S 804 690, 1000 746"/>
        <path d="M-60 858 C 196 776, 384 868, 584 820 S 808 734, 1004 790"/>
        <path d="M-60 904 C 202 820, 392 912, 592 864 S 812 778, 1008 834"/>
        <path d="M-60 950 C 208 864, 400 956, 600 908 S 816 822, 1012 878"/>
        <path d="M-60 996 C 214 908, 408 1000, 608 952 S 820 866, 1016 922"/>
        <path d="M-60 1042 C 220 952, 416 1044, 616 996 S 824 910, 1020 966"/>
        <path d="M-60 1088 C 226 996, 424 1088, 624 1040 S 828 954, 1024 1010"/>
      </g>
      <g fill="none" stroke="#e7cb92" stroke-opacity=".09" stroke-width="1">
        <path d="M-40 1100 C 120 700, 360 420, 680 250"/>
        <path d="M 90 1100 C 250 740, 470 460, 760 282"/>
        <path d="M 240 1100 C 380 800, 580 520, 842 316"/>
        <path d="M 402 1100 C 520 860, 690 580, 920 352"/>
      </g>
      <rect x="0" y="850" width="900" height="250" fill="url(#fieldHaze)"/>
    </svg>
    <div class="login-visual-inner">
      <a class="login-brand" href="login.php" aria-label="AgriTrack sign in">
        <span class="brand-mark" aria-hidden="true"><i class="bi bi-flower1"></i></span>
        <span><strong>AgriTrack</strong><small>AGRICULTURE INFORMATION SYSTEM</small></span>
      </a>
      <div class="login-statement">
        <p class="login-headline">Good records make <em>better seasons.</em></p>
        <p class="login-copy">Farmers, farms, crops, plantings, and harvests kept in one working record — so every decision starts from the same field.</p>
      </div>
      <ul class="login-facts">
        <li><i class="bi bi-people" aria-hidden="true"></i>Farmers &amp; farms</li>
        <li><i class="bi bi-calendar2-week" aria-hidden="true"></i>Planting schedule</li>
        <li><i class="bi bi-basket2" aria-hidden="true"></i>Harvest totals</li>
      </ul>
    </div>
  </section>

  <main class="login-panel">
    <div class="login-card">
      <h1>Sign in to your workspace</h1>
      <p class="login-intro">Manage farms, planting activity, and harvest records.</p>
      <?php if ($error): ?><div class="alert alert-danger" role="alert"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="csrf" value="<?= e(csrf_token()) ?>">
        <label class="form-label" for="login_email">Email address</label>
        <input class="form-control mb-3" id="login_email" type="email" name="email" required autocomplete="username" inputmode="email"<?= $error ? ' aria-invalid="true"' : '' ?>>
        <label class="form-label" for="login_password">Password</label>
        <input class="form-control mb-4" id="login_password" type="password" name="password" required autocomplete="current-password">
        <button class="btn btn-primary w-100">Sign in <i class="bi bi-arrow-right ms-1" aria-hidden="true"></i></button>
      </form>
      <div class="login-foot"><i class="bi bi-shield-lock" aria-hidden="true"></i>Authorized personnel only · Secure access</div>
    </div>
  </main>
</div>
</body>
</html>
