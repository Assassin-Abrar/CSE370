<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$stmt = $pdo->prepare('SELECT * FROM student_profiles WHERE user_id = ?');
$stmt->execute([$user['id']]);
$profile = $stmt->fetch() ?: [];

$allSkills = $pdo->query('SELECT * FROM skills ORDER BY name')->fetchAll();
$allInterests = $pdo->query('SELECT * FROM interests ORDER BY name')->fetchAll();

$stmt = $pdo->prepare('SELECT skill_id FROM student_skills WHERE user_id = ?');
$stmt->execute([$user['id']]);
$mySkillIds = array_column($stmt->fetchAll(), 'skill_id');

$stmt = $pdo->prepare('SELECT interest_id FROM student_interests WHERE user_id = ?');
$stmt->execute([$user['id']]);
$myInterestIds = array_column($stmt->fetchAll(), 'interest_id');

$pageTitle = 'My Profile';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>My Profile</h1><div class="sub">Keep your skills and interests up to date for the best club matches.</div></div>
</div>

<div class="grid grid-2" style="align-items:start;grid-template-columns:2fr 1fr;">
  <div>
    <form class="card ajax-form" action="<?= BASE_URL ?>api/update_profile.php" method="post" style="margin-bottom:20px;">
      <div class="card-header"><span class="card-title">Basic information</span></div>
      <div class="form-row">
        <div class="field"><label>Full name</label><input class="input" name="name" value="<?= e($user['name']) ?>" required></div>
        <div class="field"><label>Student ID</label><input class="input" name="student_id" value="<?= e($profile['student_id'] ?? '') ?>"></div>
      </div>
      <div class="form-row">
        <div class="field"><label>Department</label><input class="input" name="department" value="<?= e($profile['department'] ?? '') ?>" placeholder="CSE"></div>
        <div class="field"><label>Semester</label><input class="input" name="semester" value="<?= e($profile['semester'] ?? '') ?>" placeholder="5th"></div>
      </div>
      <div class="field"><label>Phone</label><input class="input" name="phone" value="<?= e($profile['phone'] ?? '') ?>"></div>
      <div class="field"><label>Bio</label><textarea class="input" name="bio" rows="3"><?= e($profile['bio'] ?? '') ?></textarea></div>

      <div class="divider"></div>
      <div class="field">
        <label>Skills</label>
        <p class="hint" style="margin-top:-4px;margin-bottom:8px;">Select the skills that best represent you — used for club matching.</p>
        <div class="chip-select">
          <?php foreach ($allSkills as $s): $checked = in_array($s['id'], $mySkillIds); ?>
            <label class="chip <?= $checked ? 'selected' : '' ?>">
              <input type="checkbox" name="skills[]" value="<?= $s['id'] ?>" <?= $checked ? 'checked' : '' ?> style="display:none;" onchange="this.closest('label').classList.toggle('selected', this.checked)">
              <?= e($s['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <div class="field">
        <label>Interests</label>
        <div class="chip-select">
          <?php foreach ($allInterests as $i): $checked = in_array($i['id'], $myInterestIds); ?>
            <label class="chip <?= $checked ? 'selected' : '' ?>">
              <input type="checkbox" name="interests[]" value="<?= $i['id'] ?>" <?= $checked ? 'checked' : '' ?> style="display:none;" onchange="this.closest('label').classList.toggle('selected', this.checked)">
              <?= e($i['name']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>
      <button class="btn btn-primary" type="submit">Save Profile</button>
    </form>

    <form class="card ajax-form" action="<?= BASE_URL ?>api/upload_cv.php" method="post" enctype="multipart/form-data">
      <div class="card-header"><span class="card-title">CV / Resume</span></div>
      <?php if (!empty($profile['cv_path'])): ?>
        <p class="text-sm" style="margin-bottom:12px;"><?= icon('file-text','icon') ?> Current file: <a href="<?= BASE_URL . e($profile['cv_path']) ?>" target="_blank" style="color:var(--primary);font-weight:600;">View uploaded CV</a></p>
      <?php endif; ?>
      <div class="field"><input class="input" type="file" name="cv" accept=".pdf,.doc,.docx"></div>
      <button class="btn btn-outline" type="submit"><?= icon('upload','icon') ?> Upload CV</button>
    </form>
  </div>

  <div>
    <div class="card" style="margin-bottom:20px;text-align:center;">
      <span class="avatar" style="width:76px;height:76px;font-size:26px;background:<?= e($user['avatar_color']) ?>;margin:0 auto 12px;"><?= e(initials($user['name'])) ?></span>
      <h3><?= e($user['name']) ?></h3>
      <p class="text-muted text-sm"><?= e($user['email']) ?></p>
      <span class="badge badge-blue">Student</span>
    </div>
    <div class="card">
      <h4 style="margin-bottom:10px;">Why this matters</h4>
      <p class="text-sm text-muted">Your skills and interests power the Smart Recruitment Matcher — the more complete your profile, the more accurate your club recommendations and match reasons will be.</p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
