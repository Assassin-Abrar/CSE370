<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/icons.php';
$user = requireRole(['student']);
$pdo = db();

$slug = $_GET['slug'] ?? '';
$stmt = $pdo->prepare('SELECT * FROM clubs WHERE slug = ? AND status = "active"');
$stmt->execute([$slug]);
$club = $stmt->fetch();
if (!$club) { http_response_code(404); die('<h1>Club not found</h1><a href="clubs.php">Back to directory</a>'); }

$match = computeClubMatch((int)$user['id'], $club);

$stmt = $pdo->prepare('SELECT s.name FROM club_skills cs JOIN skills s ON s.id=cs.skill_id WHERE cs.club_id=?');
$stmt->execute([$club['id']]);
$reqSkills = array_column($stmt->fetchAll(), 'name');
$stmt = $pdo->prepare('SELECT i.name FROM club_interests ci JOIN interests i ON i.id=ci.interest_id WHERE ci.club_id=?');
$stmt->execute([$club['id']]);
$reqInterests = array_column($stmt->fetchAll(), 'name');

$stmt = $pdo->prepare("SELECT u.*, cm.position, cm.member_role FROM club_members cm JOIN users u ON u.id=cm.user_id WHERE cm.club_id=? AND cm.status='active' ORDER BY cm.member_role='president' DESC, cm.member_role='exec' DESC, u.name");
$stmt->execute([$club['id']]);
$members = $stmt->fetchAll();
$exec = array_filter($members, fn($m) => in_array($m['member_role'], ['exec','president'], true));
$generalMembers = array_filter($members, fn($m) => $m['member_role'] === 'member');

$stmt = $pdo->prepare("SELECT * FROM recruitment_campaigns WHERE club_id=? AND status='open' ORDER BY deadline");
$stmt->execute([$club['id']]);
$campaigns = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT e.*, v.name AS venue_name FROM events e LEFT JOIN venues v ON v.id=e.venue_id WHERE e.club_id=? AND e.status='approved' AND e.event_date >= CURDATE() ORDER BY e.event_date");
$stmt->execute([$club['id']]);
$upcomingEvents = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT e.*, v.name AS venue_name FROM events e LEFT JOIN venues v ON v.id=e.venue_id WHERE e.club_id=? AND e.status IN ('completed','approved') AND e.event_date < CURDATE() ORDER BY e.event_date DESC LIMIT 6");
$stmt->execute([$club['id']]);
$pastEvents = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM collaboration_requests WHERE club_id=? ORDER BY created_at DESC LIMIT 5");
$stmt->execute([$club['id']]);
$collabRequests = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT id, status FROM applications WHERE club_id=? AND user_id=? ORDER BY id DESC LIMIT 1");
$stmt->execute([$club['id'], $user['id']]);
$myApplication = $stmt->fetch();

$isMember = false;
foreach ($members as $m) if ((int)$m['id'] === (int)$user['id']) $isMember = true;

$pageTitle = $club['name'];
require __DIR__ . '/../includes/layout_head.php';
require __DIR__ . '/../includes/app_shell_start.php';
?>
<div class="club-banner" style="background:linear-gradient(135deg, <?= e($club['logo_color']) ?>, #0f172a);">
</div>
<div class="club-profile-head">
  <div class="logo" style="background:<?= e($club['logo_color']) ?>;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:34px;"><?= e(mb_substr($club['name'],0,1)) ?></div>
  <div class="name-block" style="flex:1;min-width:0;">
    <h1><?= e($club['name']) ?></h1>
    <div class="meta-row">
      <span class="badge badge-gray"><?= e($club['category']) ?></span>
      <span class="text-sm text-muted flex-center-gap"><?= icon('users','icon') ?> <?= count($members) ?> members</span>
      <span class="text-sm text-muted flex-center-gap"><?= icon('calendar','icon') ?> Est. <?= e($club['founded_year']) ?></span>
    </div>
  </div>
  <div class="status-slot">
    <?php if ($isMember): ?>
      <span class="badge badge-green" style="padding:8px 16px;font-size:13px;"><?= icon('check-circle','icon') ?> You're a member</span>
    <?php elseif ($myApplication): ?>
      <span class="badge <?= badgeClass($myApplication['status']) ?>" style="padding:8px 16px;font-size:13px;">Application: <?= statusLabel($myApplication['status']) ?></span>
    <?php elseif ($club['recruitment_status'] === 'open'): ?>
      <button class="btn btn-primary" data-open-modal="applyModal"><?= icon('plus','icon') ?> Apply Now</button>
    <?php else: ?>
      <span class="badge badge-gray" style="padding:8px 16px;font-size:13px;">Not recruiting</span>
    <?php endif; ?>
  </div>
</div>

<div class="grid grid-2" style="grid-template-columns:2fr 1fr;align-items:start;margin-top:20px;">
  <div>
    <div class="tabs" data-tabs="#clubTabs">
      <button class="active" data-tab="about">About</button>
      <button data-tab="team">Team (<?= count($members) ?>)</button>
      <button data-tab="events">Events</button>
      <button data-tab="collab">Collaboration</button>
    </div>
    <div id="clubTabs">
      <div data-tab-panel="about">
        <div class="card" style="margin-bottom:16px;">
          <h4>About</h4><p class="text-sm text-muted"><?= nl2br(e($club['description'])) ?></p>
          <h4 style="margin-top:14px;">Mission</h4><p class="text-sm text-muted"><?= nl2br(e($club['mission'])) ?></p>
        </div>
        <div class="card" style="margin-bottom:16px;">
          <h4>Skills they recruit for</h4>
          <div class="skills-row" style="margin-top:8px;">
            <?php foreach ($reqSkills as $s): ?><span class="skill-pill"><?= e($s) ?></span><?php endforeach; ?>
          </div>
        </div>
        <?php if ($campaigns): ?>
        <div class="card">
          <h4 style="margin-bottom:10px;">Current recruitment campaigns</h4>
          <?php foreach ($campaigns as $camp): ?>
            <div class="list-item-hover" style="padding:10px;border:1px solid var(--border);border-radius:10px;margin-bottom:8px;">
              <div class="flex-between"><strong class="text-sm"><?= e($camp['title']) ?></strong><span class="badge badge-green">Open</span></div>
              <p class="text-sm text-muted" style="margin:6px 0;"><?= e($camp['description']) ?></p>
              <span class="text-xs text-subtle"><?= e($camp['positions_needed']) ?> positions · Deadline <?= date('M j, Y', strtotime($camp['deadline'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <?php endif; ?>
      </div>
      <div data-tab-panel="team" style="display:none;">
        <div class="card" style="margin-bottom:16px;">
          <h4 style="margin-bottom:12px;">Executive committee</h4>
          <div class="member-grid">
            <?php foreach ($exec as $m): ?>
              <div class="member-mini card">
                <span class="avatar" style="background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span>
                <strong class="text-sm" style="display:block;"><?= e($m['name']) ?></strong>
                <span class="text-xs text-muted"><?= e($m['position']) ?></span>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
        <div class="card">
          <h4 style="margin-bottom:12px;">Members</h4>
          <?php if (!$generalMembers): ?><p class="text-sm text-muted">No general members yet.</p><?php else: ?>
          <div class="member-grid">
            <?php foreach ($generalMembers as $m): ?>
              <div class="member-mini card">
                <span class="avatar" style="background:<?= e($m['avatar_color']) ?>;"><?= e(initials($m['name'])) ?></span>
                <strong class="text-sm" style="display:block;"><?= e($m['name']) ?></strong>
                <span class="text-xs text-muted">Member</span>
              </div>
            <?php endforeach; ?>
          </div>
          <?php endif; ?>
        </div>
      </div>
      <div data-tab-panel="events" style="display:none;">
        <div class="card" style="margin-bottom:16px;">
          <h4 style="margin-bottom:10px;">Upcoming events</h4>
          <?php if (!$upcomingEvents): ?><p class="text-sm text-muted">No upcoming events scheduled.</p><?php endif; ?>
          <?php foreach ($upcomingEvents as $ev): ?>
            <div class="list-item-hover" style="padding:10px;border-bottom:1px solid var(--border);">
              <strong class="text-sm"><?= e($ev['title']) ?></strong><br><span class="text-xs text-muted"><?= date('M j, Y', strtotime($ev['event_date'])) ?> · <?= e($ev['venue_name'] ?? 'TBA') ?></span>
            </div>
          <?php endforeach; ?>
        </div>
        <div class="card">
          <h4 style="margin-bottom:10px;">Past events</h4>
          <?php if (!$pastEvents): ?><p class="text-sm text-muted">No past events recorded.</p><?php endif; ?>
          <?php foreach ($pastEvents as $ev): ?>
            <div style="padding:10px;border-bottom:1px solid var(--border);">
              <strong class="text-sm"><?= e($ev['title']) ?></strong><br><span class="text-xs text-muted"><?= date('M j, Y', strtotime($ev['event_date'])) ?></span>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
      <div data-tab-panel="collab" style="display:none;">
        <div class="card">
          <h4 style="margin-bottom:10px;">Collaboration requests</h4>
          <?php if (!$collabRequests): ?><p class="text-sm text-muted">No open requests from this club.</p><?php endif; ?>
          <?php foreach ($collabRequests as $cr): ?>
            <div style="padding:10px;border-bottom:1px solid var(--border);">
              <div class="flex-between"><strong class="text-sm"><?= e($cr['title']) ?></strong><span class="badge <?= badgeClass($cr['status']) ?>"><?= statusLabel($cr['status']) ?></span></div>
              <p class="text-sm text-muted" style="margin:4px 0;"><?= e($cr['description']) ?></p>
            </div>
          <?php endforeach; ?>
          <a href="collaboration.php" class="btn btn-outline btn-sm" style="margin-top:10px;">View full collaboration board &rarr;</a>
        </div>
      </div>
    </div>
  </div>

  <div>
    <div class="card" style="margin-bottom:16px;">
      <h4 style="margin-bottom:12px;">Your match</h4>
      <div style="display:flex;align-items:center;gap:14px;margin-bottom:12px;">
        <div class="match-ring" style="width:72px;height:72px;">
          <svg width="72" height="72"><circle class="track" cx="36" cy="36" r="30" fill="none" stroke-width="6"/><circle cx="36" cy="36" r="30" fill="none" stroke="<?= $match['percent']>=70?'#16a34a':($match['percent']>=40?'#d97706':'#94a3b8') ?>" stroke-width="6" stroke-dasharray="<?= round(2*3.1416*30) ?>" stroke-dashoffset="<?= round(2*3.1416*30*(1-$match['percent']/100)) ?>" stroke-linecap="round"/></svg>
          <span class="pct" style="font-size:16px;"><?= $match['percent'] ?>%</span>
        </div>
        <div><strong>Match Score</strong><p class="text-xs text-muted mt-0">Based on your skills &amp; interests</p></div>
      </div>
      <strong class="text-sm">Why this club is recommended</strong>
      <ul style="margin-top:8px;">
        <?php foreach ($match['reasons'] as $r): ?>
          <li class="text-sm text-muted" style="padding:4px 0;">✓ <?= e($r) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
    <div class="card">
      <h4 style="margin-bottom:10px;">Contact</h4>
      <p class="text-sm"><?= icon('link','icon') ?> <?= e($club['email']) ?></p>
      <p class="text-sm"><?= icon('share','icon') ?> <?= e($club['social_link']) ?></p>
    </div>
  </div>
</div>

<?php if ($club['recruitment_status'] === 'open' && !$isMember && !$myApplication): ?>
<div class="modal-backdrop" id="applyModal">
  <div class="modal modal-lg">
    <div class="modal-head"><h3>Apply to <?= e($club['name']) ?></h3><button class="modal-close" data-close-modal>&times;</button></div>
    <form class="ajax-form" action="<?= BASE_URL ?>api/apply.php" method="post" enctype="multipart/form-data">
      <div class="modal-body">
        <input type="hidden" name="club_id" value="<?= $club['id'] ?>">
        <?php if ($campaigns): ?>
        <input type="hidden" name="campaign_id" value="<?= $campaigns[0]['id'] ?>">
        <?php endif; ?>
        <div class="field"><label>Motivation *</label><textarea class="input" name="motivation" rows="3" required placeholder="Why do you want to join this club?"></textarea></div>
        <div class="field"><label>Relevant skills *</label><input class="input" name="skills_text" required placeholder="e.g. Programming, Web Development"></div>
        <div class="field"><label>Experience</label><textarea class="input" name="experience" rows="2" placeholder="Relevant experience, projects or achievements"></textarea></div>
        <div class="form-row">
          <div class="field"><label>Portfolio link</label><input class="input" name="portfolio_link" placeholder="https://..."></div>
          <div class="field"><label>Availability</label><input class="input" name="availability" placeholder="Evenings & weekends"></div>
        </div>
        <div class="field"><label>CV (optional)</label><input class="input" type="file" name="cv" accept=".pdf,.doc,.docx"></div>
      </div>
      <div class="modal-foot">
        <button type="button" class="btn" data-close-modal>Cancel</button>
        <button type="submit" class="btn btn-primary">Submit Application</button>
      </div>
    </form>
  </div>
</div>
<?php endif; ?>

<?php require __DIR__ . '/../includes/app_shell_end.php'; ?>
