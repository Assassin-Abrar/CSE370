<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';

if (isLoggedIn()) {
    $u = currentUser();
    if ($u) redirectTo($u['role'] . '/dashboard.php');
}

$errors = [];
$name = $email = $studentId = $department = $semester = '';

if (isPost()) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $studentId = trim($_POST['student_id'] ?? '');
    $department = trim($_POST['department'] ?? '');
    $semester = trim($_POST['semester'] ?? '');

    if ($name === '' || $email === '' || $password === '') $errors[] = 'Name, email and password are required.';
    if ($password !== '' && strlen($password) < 6) $errors[] = 'Password must be at least 6 characters.';
    if ($password !== $confirm) $errors[] = 'Passwords do not match.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address.';

    if (!$errors) {
        $stmt = db()->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) $errors[] = 'An account with this email already exists.';
    }

    if (!$errors) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $colors = ['#4f46e5','#0891b2','#059669','#b45309','#be185d','#7c3aed'];
            $color = $colors[array_rand($colors)];
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, avatar_color) VALUES (?,?,?,?,?)');
            $stmt->execute([$name, $email, $hash, 'student', $color]);
            $userId = (int)$pdo->lastInsertId();
            $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, student_id, department, semester) VALUES (?,?,?,?)');
            $stmt->execute([$userId, $studentId, $department, $semester]);
            $pdo->commit();
            notify($userId, 'welcome', 'Welcome to BRACU CMS', 'Complete your profile with skills and interests to get personalized club recommendations.', '/student/profile.php');
            redirectTo('auth/login.php?registered=1');
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = 'Something went wrong creating your account. Please try again.';
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/../includes/layout_head.php';
?>
<body>
<div class="auth-wrap">
  <div class="auth-brand">
    <h1>Join the BRACU community</h1>
    <p>Create your student account to discover clubs matched to your skills, register for events, and track your applications.</p>
  </div>
  <div class="auth-panel" style="position:relative;">
    <button class="icon-btn" data-theme-toggle aria-label="Toggle theme" style="position:absolute;top:24px;right:24px;"><?= icon('moon') ?></button>
    <div class="auth-form" style="max-width:420px;">
      <div class="logo"><span class="mark" style="width:32px;height:32px;border-radius:9px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;">B</span> BRACU CMS</div>
      <h2 style="margin-bottom:6px;">Create your account</h2>
      <p class="text-muted text-sm" style="margin-bottom:22px;">Student sign-up. Club executives and OCA staff accounts are provisioned by the administration.</p>

      <?php if ($errors): ?>
        <div class="field"><div class="error" style="background:var(--red-light);padding:10px 12px;border-radius:8px;"><?php foreach($errors as $er) echo e($er).'<br>'; ?></div></div>
      <?php endif; ?>

      <form method="post" novalidate>
        <div class="field">
          <label for="name">Full name</label>
          <input class="input" type="text" id="name" name="name" value="<?= e($name) ?>" required>
        </div>
        <div class="field">
          <label for="email">BRACU email</label>
          <input class="input" type="email" id="email" name="email" value="<?= e($email) ?>" required>
        </div>
        <div class="form-row">
          <div class="field">
            <label for="student_id">Student ID</label>
            <input class="input" type="text" id="student_id" name="student_id" value="<?= e($studentId) ?>" placeholder="22101045">
          </div>
          <div class="field">
            <label for="semester">Semester</label>
            <input class="input" type="text" id="semester" name="semester" value="<?= e($semester) ?>" placeholder="5th">
          </div>
        </div>
        <div class="field">
          <label for="department">Department</label>
          <input class="input" type="text" id="department" name="department" value="<?= e($department) ?>" placeholder="CSE">
        </div>
        <div class="form-row">
          <div class="field">
            <label for="password">Password</label>
            <input class="input" type="password" id="password" name="password" required>
          </div>
          <div class="field">
            <label for="confirm_password">Confirm password</label>
            <input class="input" type="password" id="confirm_password" name="confirm_password" required>
          </div>
        </div>
        <button class="btn btn-primary btn-block" type="submit">Create account</button>
      </form>
      <p class="text-sm text-muted" style="margin-top:18px;text-align:center;">Already have an account? <a href="login.php" style="color:var(--primary);font-weight:600;">Log in</a></p>
    </div>
  </div>
</div>
<?php require __DIR__ . '/../includes/simple_foot.php'; ?>
