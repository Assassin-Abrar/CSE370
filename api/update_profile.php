<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['student']);
requireCsrfOrFail();

$name = trim($_POST['name'] ?? $user['name']);
$studentId = trim($_POST['student_id'] ?? '');
$department = trim($_POST['department'] ?? '');
$semester = trim($_POST['semester'] ?? '');
$bio = trim($_POST['bio'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$skillIds = array_map('intval', $_POST['skills'] ?? []);
$interestIds = array_map('intval', $_POST['interests'] ?? []);

if ($name === '') jsonResponse(['ok' => false, 'message' => 'Name cannot be empty.'], 422);

$pdo = db();
$pdo->beginTransaction();
try {
    $stmt = $pdo->prepare('UPDATE users SET name = ? WHERE id = ?');
    $stmt->execute([$name, $user['id']]);

    $stmt = $pdo->prepare('SELECT user_id FROM student_profiles WHERE user_id = ?');
    $stmt->execute([$user['id']]);
    if ($stmt->fetch()) {
        $stmt = $pdo->prepare('UPDATE student_profiles SET student_id=?, department=?, semester=?, bio=?, phone=? WHERE user_id=?');
        $stmt->execute([$studentId, $department, $semester, $bio, $phone, $user['id']]);
    } else {
        $stmt = $pdo->prepare('INSERT INTO student_profiles (user_id, student_id, department, semester, bio, phone) VALUES (?,?,?,?,?,?)');
        $stmt->execute([$user['id'], $studentId, $department, $semester, $bio, $phone]);
    }

    $pdo->prepare('DELETE FROM student_skills WHERE user_id = ?')->execute([$user['id']]);
    if ($skillIds) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO student_skills (user_id, skill_id) VALUES (?,?)');
        foreach ($skillIds as $sid) $stmt->execute([$user['id'], $sid]);
    }

    $pdo->prepare('DELETE FROM student_interests WHERE user_id = ?')->execute([$user['id']]);
    if ($interestIds) {
        $stmt = $pdo->prepare('INSERT IGNORE INTO student_interests (user_id, interest_id) VALUES (?,?)');
        foreach ($interestIds as $iid) $stmt->execute([$user['id'], $iid]);
    }

    $pdo->commit();
} catch (Exception $e) {
    $pdo->rollBack();
    jsonResponse(['ok' => false, 'message' => 'Could not save your profile. Please try again.'], 500);
}

jsonResponse(['ok' => true, 'message' => 'Profile updated. Your club recommendations have been refreshed.', 'reload' => true]);
