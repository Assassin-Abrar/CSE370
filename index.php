<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/icons.php';

if (isLoggedIn()) {
    $u = currentUser();
    if ($u) redirectTo($u['role'] . '/dashboard.php');
}

$pdo = db();
$stats = [
    'clubs' => (int)$pdo->query("SELECT COUNT(*) FROM clubs WHERE status='active'")->fetchColumn(),
    'students' => (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='student'")->fetchColumn(),
    'events' => (int)$pdo->query("SELECT COUNT(*) FROM events WHERE status IN ('approved','completed')")->fetchColumn(),
    'applications' => (int)$pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
];
$featuredClubs = $pdo->query("SELECT * FROM clubs WHERE status='active' ORDER BY RAND() LIMIT 4")->fetchAll();

$pageTitle = 'Home';
require __DIR__ . '/includes/layout_head.php';
?>
<body>
<nav class="nav-public">
  <div class="container">
    <a href="<?= BASE_URL ?>" style="display:flex;align-items:center;gap:10px;font-weight:800;">
      <span class="mark" style="width:32px;height:32px;border-radius:9px;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;">B</span>
      BRACU CMS
    </a>
    <div class="links">
      <a href="#how-it-works">How it works</a>
      <a href="#features">Features</a>
      <a href="#stats">Impact</a>
    </div>
    <div style="display:flex;gap:10px;align-items:center;">
      <button class="icon-btn" data-theme-toggle aria-label="Toggle theme"><?= icon('moon') ?></button>
      <a href="auth/login.php" class="btn btn-outline btn-sm">Log in</a>
      <a href="auth/register.php" class="btn btn-primary btn-sm">Get Started</a>
    </div>
  </div>
</nav>

<header class="hero">
  <div class="container">
    <span class="kicker"><?= icon('zap','icon') ?> One platform for every BRACU club</span>
    <h1>BRACU Club Management System</h1>
    <p class="lead">One platform for discovering clubs, managing events, collaborating, recruiting members, and building a stronger BRACU community — replacing scattered Facebook groups, Messenger threads and spreadsheets.</p>
    <div class="cta-row">
      <a href="student/clubs.php" onclick="return false;" class="btn btn-outline" style="pointer-events:none;opacity:.5;" title="Log in to explore">Explore Clubs</a>
      <a href="auth/register.php" class="btn btn-primary"><?= icon('arrow-right','icon') ?> Get Started</a>
    </div>
  </div>
</header>

<section class="landing-section" id="how-it-works">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">How it works</div>
      <h2>From discovery to delivery, in one flow</h2>
    </div>
    <div class="grid grid-3">
      <div class="card step">
        <div class="num">1</div>
        <div><h3>Discover &amp; match</h3><p class="text-muted text-sm">Tell us your skills and interests — get ranked club recommendations with a clear match score.</p></div>
      </div>
      <div class="card step">
        <div class="num">2</div>
        <div><h3>Apply &amp; join</h3><p class="text-muted text-sm">Apply directly, track your status through the recruitment pipeline, and get accepted.</p></div>
      </div>
      <div class="card step">
        <div class="num">3</div>
        <div><h3>Collaborate &amp; grow</h3><p class="text-muted text-sm">Run events, manage budgets, delegate tasks, and collaborate across clubs — transparently.</p></div>
      </div>
    </div>
  </div>
</section>

<section class="landing-section alt" id="features">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">Everything you need</div>
      <h2>Built for students, executives &amp; OCA</h2>
    </div>
    <div class="grid grid-3">
      <div class="card feature-card card-hover">
        <div class="icon-wrap" style="background:var(--primary-light);color:var(--primary-dark);"><?= icon('compass') ?></div>
        <h3>Smart Club Matching</h3>
        <p>A recommendation engine compares your skills and interests against every club's needs to surface your best-fit clubs first.</p>
      </div>
      <div class="card feature-card card-hover">
        <div class="icon-wrap" style="background:var(--blue-light);color:var(--blue-dark);"><?= icon('calendar') ?></div>
        <h3>Event Management</h3>
        <p>Propose events with automatic venue clash detection and major-event conflict warnings before you submit.</p>
      </div>
      <div class="card feature-card card-hover">
        <div class="icon-wrap" style="background:var(--purple-light);color:var(--purple-dark);"><?= icon('share') ?></div>
        <h3>Inter-Club Collaboration</h3>
        <p>Post equipment, volunteer or co-host requests and let other clubs respond directly — no more scattered Messenger threads.</p>
      </div>
      <div class="card feature-card card-hover">
        <div class="icon-wrap" style="background:var(--green-light);color:var(--green-dark);"><?= icon('dollar') ?></div>
        <h3>Transparent Budgets</h3>
        <p>Submit itemized budget proposals and track every status change with a full audit trail — from draft to allocation.</p>
      </div>
      <div class="card feature-card card-hover">
        <div class="icon-wrap" style="background:var(--amber-light);color:var(--amber-dark);"><?= icon('check-square') ?></div>
        <h3>Task Delegation</h3>
        <p>Assign event-day tasks, track progress on a kanban board, and reassign work before anyone burns out.</p>
      </div>
      <div class="card feature-card card-hover">
        <div class="icon-wrap" style="background:var(--red-light);color:var(--red-dark);"><?= icon('bar-chart') ?></div>
        <h3>Workload Analysis</h3>
        <p>Automatic overload detection flags stretched executives and recommends who has capacity to take on more.</p>
      </div>
    </div>
  </div>
</section>

<section class="landing-section" id="stats">
  <div class="container">
    <div class="card" style="padding:40px;">
      <div class="stats-row">
        <div><div class="num"><?= $stats['clubs'] ?>+</div><div class="lbl">Active Clubs</div></div>
        <div><div class="num"><?= $stats['students'] ?>+</div><div class="lbl">Registered Students</div></div>
        <div><div class="num"><?= $stats['events'] ?>+</div><div class="lbl">Events Managed</div></div>
        <div><div class="num"><?= $stats['applications'] ?>+</div><div class="lbl">Applications Processed</div></div>
      </div>
    </div>
  </div>
</section>

<section class="landing-section alt">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">Featured clubs</div>
      <h2>Join a community that fits you</h2>
    </div>
    <div class="grid grid-4">
      <?php foreach ($featuredClubs as $c): ?>
        <div class="card card-hover club-card">
          <div class="head-row">
            <div class="logo" style="background:<?= e($c['logo_color']) ?>"><?= e(mb_substr($c['name'],0,1)) ?></div>
            <div>
              <h3 class="text-lg"><?= e($c['name']) ?></h3>
              <span class="badge badge-gray"><?= e($c['category']) ?></span>
            </div>
          </div>
          <p class="text-muted text-sm"><?= e(mb_substr($c['description'],0,90)) ?>…</p>
          <?= $c['recruitment_status']==='open' ? '<span class="badge badge-green">Recruiting</span>' : '<span class="badge badge-gray">Closed</span>' ?>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="landing-section">
  <div class="container">
    <div class="section-head">
      <div class="eyebrow">What people say</div>
      <h2>Trusted across campus</h2>
    </div>
    <div class="grid grid-3">
      <div class="card testimonial">
        <p class="quote">"I found my club in five minutes instead of scrolling ten Facebook groups. The match score was spot on."</p>
        <div class="who"><span class="avatar" style="width:34px;height:34px;background:#4f46e5;">RI</span><div><div class="name">Rakibul Islam</div><div class="role">CSE, Student</div></div></div>
      </div>
      <div class="card testimonial">
        <p class="quote">"Budget approvals used to take weeks of email chasing. Now I can see exactly where our request stands."</p>
        <div class="who"><span class="avatar" style="width:34px;height:34px;background:#7c3aed;">SR</span><div><div class="name">Shuvo Roy</div><div class="role">President, Uddipana</div></div></div>
      </div>
      <div class="card testimonial">
        <p class="quote">"The venue clash detector saved us from double-booking the Multipurpose Hall during TechFest week."</p>
        <div class="who"><span class="avatar" style="width:34px;height:34px;background:#0f172a;">FR</span><div><div class="name">Dr. Farhana Rahman</div><div class="role">OCA Administrator</div></div></div>
      </div>
    </div>
  </div>
</section>

<section class="landing-section alt">
  <div class="container" style="text-align:center;">
    <h2 style="font-size:28px;margin-bottom:10px;">Ready to find your community?</h2>
    <p class="text-muted" style="margin-bottom:24px;">Create your free account and get matched with clubs in minutes.</p>
    <a href="auth/register.php" class="btn btn-primary" style="padding:13px 28px;">Get Started Free</a>
  </div>
</section>

<footer class="footer-public">
  <div class="container">
    <div class="grid grid-4">
      <div>
        <h4>BRACU CMS</h4>
        <p style="font-size:13px;">The centralized platform for extracurricular life at BRAC University.</p>
      </div>
      <div>
        <h4>Platform</h4>
        <a href="auth/login.php">Log in</a>
        <a href="auth/register.php">Get Started</a>
      </div>
      <div>
        <h4>Roles</h4>
        <a href="auth/login.php">Student</a>
        <a href="auth/login.php">Club Executive</a>
        <a href="auth/login.php">OCA Admin</a>
      </div>
      <div>
        <h4>Office of Co-Curricular Activities</h4>
        <a href="#">oca@bracu.ac.bd</a>
        <a href="#">UB2, BRAC University</a>
      </div>
    </div>
    <div class="footer-bottom">Demo project — BRACU Club Management System. Not an official BRAC University product.</div>
  </div>
</footer>
<?php require __DIR__ . '/includes/simple_foot.php'; ?>
