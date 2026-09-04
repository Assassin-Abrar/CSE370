<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$q = trim($_GET['q'] ?? '');
$category = $_GET['category'] ?? '';
$recruiting = $_GET['recruiting'] ?? '';
$sort = $_GET['sort'] ?? 'match';

$clubs = getAllClubMatches((int)$user['id']);

if ($q !== '') {
    $clubs = array_filter($clubs, fn($c) => stripos($c['name'], $q) !== false || stripos($c['description'], $q) !== false);
}
if ($category !== '') {
    $clubs = array_filter($clubs, fn($c) => $c['category'] === $category);
}
if ($recruiting === '1') {
    $clubs = array_filter($clubs, fn($c) => $c['recruitment_status'] === 'open');
}
$clubs = array_values($clubs);

switch ($sort) {
    case 'name': usort($clubs, fn($a, $b) => strcmp($a['name'], $b['name'])); break;
    case 'members':
        foreach ($clubs as &$c) {
            $s = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id = ? AND status='active'");
            $s->execute([$c['id']]); $c['member_count'] = (int)$s->fetchColumn();
        } unset($c);
        usort($clubs, fn($a, $b) => $b['member_count'] <=> $a['member_count']);
        break;
    case 'skill': usort($clubs, fn($a, $b) => $b['match']['skill_score'] <=> $a['match']['skill_score']); break;
    case 'interest': usort($clubs, fn($a, $b) => $b['match']['interest_score'] <=> $a['match']['interest_score']); break;
    default: usort($clubs, fn($a, $b) => $b['match']['percent'] <=> $a['match']['percent']);
}

$categories = $pdo->query('SELECT DISTINCT category FROM clubs ORDER BY category')->fetchAll(PDO::FETCH_COLUMN);

function memberCountOf(PDO $pdo, int $clubId): int {
    $s = $pdo->prepare("SELECT COUNT(*) FROM club_members WHERE club_id = ? AND status='active'");
    $s->execute([$clubId]);
    return (int)$s->fetchColumn();
}

$pageTitle = 'Explore Clubs';
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="page-head">
  <div><h1>Explore Clubs</h1><div class="sub">Ranked by how well they match your skills and interests.</div></div>
</div>

<form class="filter-bar" method="get">
  <input class="input" type="search" name="q" placeholder="Search clubs..." value="<?= e($q) ?>" style="max-width:220px;">
  <select class="input" name="category" onchange="this.form.submit()">
    <option value="">All Categories</option>
    <?php foreach ($categories as $cat): ?>
      <option value="<?= e($cat) ?>" <?= $category === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
    <?php endforeach; ?>
  </select>
  <select class="input" name="sort" onchange="this.form.submit()">
    <option value="match" <?= $sort==='match'?'selected':'' ?>>Highest Match</option>
    <option value="skill" <?= $sort==='skill'?'selected':'' ?>>Skill Match</option>
    <option value="interest" <?= $sort==='interest'?'selected':'' ?>>Interest Match</option>
    <option value="members" <?= $sort==='members'?'selected':'' ?>>Most Members</option>
    <option value="name" <?= $sort==='name'?'selected':'' ?>>Name (A-Z)</option>
  </select>
  <label class="chip <?= $recruiting==='1' ? 'selected':'' ?>" style="cursor:pointer;">
    <input type="checkbox" name="recruiting" value="1" <?= $recruiting==='1'?'checked':'' ?> onchange="this.form.submit()" style="display:none;">
    Currently Recruiting Only
  </label>
  <button class="btn btn-primary btn-sm" type="submit">Apply</button>
  <?php if ($q || $category || $recruiting): ?><a href="clubs.php" class="btn btn-ghost btn-sm">Clear</a><?php endif; ?>
</form>

<?php if (!$clubs): ?>
  <div class="empty-state"><div class="icon">🔍</div><h4>No clubs found</h4><p>Try adjusting your search or filters.</p></div>
<?php else: ?>
<div class="grid grid-3">
  <?php foreach ($clubs as $c): $m = $c['match']; ?>
    <a href="club.php?slug=<?= e($c['slug']) ?>" class="card card-hover club-card" style="text-decoration:none;color:inherit;">
      <div class="head-row">
        <div class="logo" style="background:<?= e($c['logo_color']) ?>"><?= e(mb_substr($c['name'],0,1)) ?></div>
        <div style="flex:1;">
          <h3 class="text-lg"><?= e($c['name']) ?></h3>
          <span class="badge badge-gray"><?= e($c['category']) ?></span>
        </div>
        <div class="match-ring">
          <svg width="56" height="56"><circle class="track" cx="28" cy="28" r="24" fill="none" stroke-width="5"/><circle cx="28" cy="28" r="24" fill="none" stroke="<?= $m['percent']>=70?'#16a34a':($m['percent']>=40?'#d97706':'#94a3b8') ?>" stroke-width="5" stroke-dasharray="<?= round(2*3.1416*24) ?>" stroke-dashoffset="<?= round(2*3.1416*24*(1-$m['percent']/100)) ?>" stroke-linecap="round"/></svg>
          <span class="pct"><?= $m['percent'] ?>%</span>
        </div>
      </div>
      <p class="text-muted text-sm"><?= e(mb_substr($c['description'], 0, 100)) ?>…</p>
      <div class="skills-row">
        <?php foreach (array_slice(explode(',', implode(',', $m['matched_skills'])), 0, 3) as $sk): if($sk==='')continue; ?>
          <span class="skill-pill" style="background:var(--green-light);color:var(--green-dark);">✓ <?= e($sk) ?></span>
        <?php endforeach; ?>
      </div>
      <div class="flex-between">
        <span class="text-xs text-muted"><?= memberCountOf($pdo, $c['id']) ?> members</span>
        <?= $c['recruitment_status']==='open' ? '<span class="badge badge-green">Recruiting</span>' : '<span class="badge badge-gray">Closed</span>' ?>
      </div>
    </a>
  <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
