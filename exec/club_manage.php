<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['exec']);
$club = myClub($user);
if (!$club) { die('<p style="padding:40px;">Not linked to a club.</p>'); }
$pdo = db();

$allSkills = $pdo->query('SELECT * FROM skills ORDER BY name')->fetchAll();
$allInterests = $pdo->query('SELECT * FROM interests ORDER BY name')->fetchAll();
$stmt = $pdo->prepare('SELECT skill_id FROM club_skills WHERE club_id=?'); $stmt->execute([$club['id']]);
$mySkillIds = array_column($stmt->fetchAll(), 'skill_id');
$stmt = $pdo->prepare('SELECT interest_id FROM club_interests WHERE club_id=?'); $stmt->execute([$club['id']]);
$myInterestIds = array_column($stmt->fetchAll(), 'interest_id');

$stmt = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id=? AND status='active'"); $stmt->execute([$club['id']]);
$memberCount = (int)$stmt->fetchColumn();

$pageTitle = 'My Club';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head"><div><h1>My Club</h1><div class="sub">Manage your club's public profile and recruitment settings.</div></div></div>

<div class="grid grid-2" style="grid-template-columns:2fr 1fr;align-items:start;">
  <form class="card ajax-form" action="<?= BASE_URL ?>api/update_club.php" method="post">
    <div class="card-header"><span class="card-title"><?= e($club['name']) ?></span><span class="badge badge-gray"><?= e($club['category']) ?></span></div>
    <div class="field"><label>About / Description</label><textarea class="input" name="description" rows="3"><?= e($club['description']) ?></textarea></div>
    <div class="field"><label>Mission</label><textarea class="input" name="mission" rows="2"><?= e($club['mission']) ?></textarea></div>
    <div class="form-row">
      <div class="field"><label>Contact email</label><input class="input" name="email" value="<?= e($club['email']) ?>"></div>
      <div class="field"><label>Social link</label><input class="input" name="social_link" value="<?= e($club['social_link']) ?>"></div>
    </div>
    <div class="field checkbox-row">
      <input type="checkbox" id="recruitment_status" name="recruitment_status" value="open" <?= $club['recruitment_status']==='open'?'checked':'' ?>>
      <label for="recruitment_status" style="margin-bottom:0;">Currently recruiting new members</label>
    </div>
    <div class="divider"></div>
    <div class="field">
      <label>Skills recruited for</label>
      <div class="chip-select">
        <?php foreach ($allSkills as $s): $checked = in_array($s['id'], $mySkillIds); ?>
          <label class="chip <?= $checked?'selected':'' ?>">
            <input type="checkbox" name="skills[]" value="<?= $s['id'] ?>" <?= $checked?'checked':'' ?> style="display:none;" onchange="this.closest('label').classList.toggle('selected', this.checked)">
            <?= e($s['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <div class="field">
      <label>Interest categories</label>
      <div class="chip-select">
        <?php foreach ($allInterests as $i): $checked = in_array($i['id'], $myInterestIds); ?>
          <label class="chip <?= $checked?'selected':'' ?>">
            <input type="checkbox" name="interests[]" value="<?= $i['id'] ?>" <?= $checked?'checked':'' ?> style="display:none;" onchange="this.closest('label').classList.toggle('selected', this.checked)">
            <?= e($i['name']) ?>
          </label>
        <?php endforeach; ?>
      </div>
    </div>
    <button class="btn btn-primary" type="submit">Save Changes</button>
  </form>

  <div>
    <div class="card" style="margin-bottom:16px;text-align:center;">
      <div class="logo" style="width:70px;height:70px;border-radius:18px;background:<?= e($club['logo_color']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:26px;margin:0 auto 12px;"><?= e(mb_substr($club['name'],0,1)) ?></div>
      <h3><?= e($club['name']) ?></h3>
      <p class="text-sm text-muted"><?= $memberCount ?> active members · Est. <?= e($club['founded_year']) ?></p>
      <a href="<?= BASE_URL ?>student/club.php?slug=<?= e($club['slug']) ?>" class="btn btn-outline btn-sm" style="margin-top:8px;">View public profile</a>
    </div>
    <div class="card">
      <h4 style="margin-bottom:8px;">Recruitment status</h4>
      <span class="badge <?= $club['recruitment_status']==='open'?'badge-green':'badge-gray' ?>" style="padding:8px 14px;"><?= $club['recruitment_status']==='open'?'Currently Recruiting':'Not Recruiting' ?></span>
      <p class="text-sm text-muted" style="margin-top:10px;">Toggle recruitment and set skill/interest tags to control who sees your club as a top match.</p>
    </div>
  </div>
</div>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
