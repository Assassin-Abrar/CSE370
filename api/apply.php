<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['student']);
requireCsrfOrFail();

$clubId = (int)($_POST['club_id'] ?? 0);
$campaignId = !empty($_POST['campaign_id']) ? (int)$_POST['campaign_id'] : null;
$motivation = trim($_POST['motivation'] ?? '');
$skillsText = trim($_POST['skills_text'] ?? '');
$experience = trim($_POST['experience'] ?? '');
$portfolio = trim($_POST['portfolio_link'] ?? '');
$availability = trim($_POST['availability'] ?? '');

$errors = [];
if (!$clubId) $errors['club_id'] = 'Missing club.';
if ($motivation === '') $errors['motivation'] = 'Please tell us why you want to join.';
if ($skillsText === '') $errors['skills_text'] = 'Please list relevant skills.';
if ($errors) jsonResponse(['ok' => false, 'message' => 'Please fill in the required fields.', 'errors' => $errors], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT * FROM clubs WHERE id = ? AND status = "active"');
$stmt->execute([$clubId]);
$club = $stmt->fetch();
if (!$club) jsonResponse(['ok' => false, 'message' => 'Club not found.'], 404);
if ($club['recruitment_status'] !== 'open') jsonResponse(['ok' => false, 'message' => 'This club is not currently recruiting.'], 422);

$stmt = $pdo->prepare("SELECT id, status FROM applications WHERE club_id = ? AND user_id = ? AND status NOT IN ('rejected') ORDER BY id DESC LIMIT 1");
$stmt->execute([$clubId, $user['id']]);
if ($existing = $stmt->fetch()) {
    jsonResponse(['ok' => false, 'message' => 'You already have an active application to this club (' . statusLabel($existing['status']) . ').'], 422);
}

$cvPath = null;
if (!empty($_FILES['cv']) && $_FILES['cv']['error'] === UPLOAD_ERR_OK) {
    $ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
    if (in_array($ext, ['pdf', 'doc', 'docx'], true) && $_FILES['cv']['size'] <= 5 * 1024 * 1024) {
        $fname = 'cv_' . $user['id'] . '_' . time() . '.' . $ext;
        if (!is_dir(UPLOAD_DIR_CV)) mkdir(UPLOAD_DIR_CV, 0777, true);
        if (move_uploaded_file($_FILES['cv']['tmp_name'], UPLOAD_DIR_CV . $fname)) {
            $cvPath = 'uploads/cv/' . $fname;
        }
    } else {
        jsonResponse(['ok' => false, 'message' => 'CV must be a PDF/DOC/DOCX under 5MB.'], 422);
    }
}

$stmt = $pdo->prepare('INSERT INTO applications (campaign_id, club_id, user_id, motivation, skills_text, experience, portfolio_link, cv_path, availability, status) VALUES (?,?,?,?,?,?,?,?,?,"submitted")');
$stmt->execute([$campaignId, $clubId, $user['id'], $motivation, $skillsText, $experience, $portfolio, $cvPath, $availability]);
$appId = (int)$pdo->lastInsertId();

$stmt = $pdo->prepare("SELECT user_id FROM club_members WHERE club_id = ? AND member_role IN ('exec','president')");
$stmt->execute([$clubId]);
foreach ($stmt->fetchAll() as $row) {
    notify((int)$row['user_id'], 'application', 'New application received', $user['name'] . ' applied to ' . $club['name'] . '.', '/exec/recruitment.php');
}
logAudit($user['id'], 'submitted_application', 'application', $appId, 'Applied to ' . $club['name']);

jsonResponse(['ok' => true, 'message' => 'Application submitted! Track its status under My Applications.', 'reload' => true, 'closeModal' => true]);
