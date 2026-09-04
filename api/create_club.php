<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$name = trim($_POST['name'] ?? '');
$category = trim($_POST['category'] ?? '');
$description = trim($_POST['description'] ?? '');
$foundedYear = (int)($_POST['founded_year'] ?? date('Y'));
$presidentEmail = trim($_POST['president_email'] ?? '');
$presidentName = trim($_POST['president_name'] ?? '');

if ($name === '' || $category === '') jsonResponse(['ok' => false, 'message' => 'Name and category are required.'], 422);

$slug = strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $name), '-'));
$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM clubs WHERE slug = ?');
$stmt->execute([$slug]);
if ($stmt->fetch()) jsonResponse(['ok' => false, 'message' => 'A club with a similar name already exists.'], 422);

$colors = ['#4f46e5','#0891b2','#059669','#b45309','#be185d','#7c3aed','#16a34a','#4338ca'];
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('INSERT INTO clubs (name, slug, category, description, mission, logo_color, founded_year, recruitment_status, status) VALUES (?,?,?,?,?,?,?,"closed","active")');
    $stmt->execute([$name, $slug, $category, $description, '', $colors[array_rand($colors)], $foundedYear]);
    $clubId = (int)$pdo->lastInsertId();

    if ($presidentEmail !== '' && $presidentName !== '') {
        $stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
        $stmt->execute([$presidentEmail]);
        $existing = $stmt->fetch();
        if ($existing) {
            $presidentId = (int)$existing['id'];
            $pdo->prepare("UPDATE users SET role='exec' WHERE id=?")->execute([$presidentId]);
        } else {
            $hash = password_hash('password123', PASSWORD_BCRYPT);
            $stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, avatar_color) VALUES (?,?,?,"exec",?)');
            $stmt->execute([$presidentName, $presidentEmail, $hash, $colors[array_rand($colors)]]);
            $presidentId = (int)$pdo->lastInsertId();
        }
        $stmt = $pdo->prepare('INSERT INTO club_members (club_id, user_id, position, member_role, status) VALUES (?,?,"President","president","active")');
        $stmt->execute([$clubId, $presidentId]);
    }
    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['ok' => false, 'message' => 'Could not create club.'], 500);
}

logAudit($user['id'], 'created_club', 'club', $clubId, $name);
jsonResponse(['ok' => true, 'message' => 'Club created.', 'reload' => true, 'closeModal' => true]);
