<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';

if (isLoggedIn()) {
    $u = currentUser();
    if ($u) redirectTo($u['role'] . '/dashboard.php');
}

$errors = [];
$email = '';

if (isPost()) {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both email and password.';
    } else {
        $stmt = db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = 'Invalid email or password.';
        } elseif ($user['status'] !== 'active') {
            $errors[] = 'This account has been suspended. Contact OCA for assistance.';
        } else {
            loginUser($user);
            redirectTo($user['role'] . '/dashboard.php');
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/../includes/layout_head.php';
?>
<body>
<div class="auth-wrap">
  <div class="auth-brand">
    <h1>Welcome back to BRACU CMS</h1>
    <p>Track your applications, manage your club, or review campus-wide activity — all from one dashboard.</p>
  </div>
  <div class="auth-panel" style="position:relative;">
    <button class="icon-btn" data-theme-toggle aria-label="Toggle theme" style="position:absolute;top:24px;right:24px;"><?= icon('moon') ?></button>
    <div class="auth-form">
      <div class="logo"><span class="mark" style="width:32px;height:32px;border-radius:9px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;">B</span> BRACU CMS</div>
      <h2 style="margin-bottom:6px;">Log in</h2>
      <p class="text-muted text-sm" style="margin-bottom:22px;">Enter your credentials to access your dashboard.</p>

      <?php if ($errors): ?>
        <div class="field"><div class="error" style="background:var(--red-light);padding:10px 12px;border-radius:8px;"><?= e($errors[0]) ?></div></div>
      <?php endif; ?>
      <?php if (!empty($_GET['suspended'])): ?>
        <div class="field"><div class="error" style="background:var(--red-light);padding:10px 12px;border-radius:8px;">Your account was suspended. Contact OCA.</div></div>
      <?php endif; ?>
      <?php if (!empty($_GET['registered'])): ?>
        <div class="field"><div style="background:var(--green-light);color:var(--green-dark);padding:10px 12px;border-radius:8px;font-size:13px;">Account created! You can log in now.</div></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="field">
          <label for="email">Email</label>
          <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>" required autofocus>
        </div>
        <div class="field">
          <label for="password">Password</label>
          <input class="input" type="password" id="password" name="password" required>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Log in</button>
      </form>

      <div class="demo-box">
        <strong>Demo accounts</strong> (password: <code>password123</code>)<br>
        Student: <code>rakibul.islam@bracu.ac.bd</code><br>
        Club Exec: <code>sarah.ahmed@bracu.ac.bd</code> (Computer Club)<br>
        OCA Admin: <code>admin@bracu.ac.bd</code>
      </div>
      <p class="text-sm text-muted" style="margin-top:18px;text-align:center;">New student? <a href="register.php" style="color:var(--primary);font-weight:600;">Create an account</a></p>
      <p class="text-sm" style="margin-top:8px;text-align:center;"><a href="<?= BASE_URL ?>" style="color:var(--text-muted);">&larr; Back to home</a></p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/simple_foot.php'; ?>
