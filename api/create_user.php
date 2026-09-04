<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['admin']);
requireCsrfOrFail();

$name = trim($_POST['name'] ?? '');
$email = trim($_POST['email'] ?? '');
$role = $_POST['role'] ?? 'student';
$password = $_POST['password'] ?? '';

$errors = [];
if ($name === '') $errors['name'] = 'Name is required.';
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors['email'] = 'A valid email is required.';
if (!in_array($role, ['student', 'exec', 'admin'], true)) $errors['role'] = 'Invalid role.';
if ($password !== '' && strlen($password) < 6) $errors['password'] = 'Password must be at least 6 characters.';
if ($errors) jsonResponse(['ok' => false, 'message' => 'Please fix the errors below.', 'errors' => $errors], 422);

$pdo = db();
$stmt = $pdo->prepare('SELECT id FROM users WHERE email = ?');
$stmt->execute([$email]);
if ($stmt->fetch()) jsonResponse(['ok' => false, 'message' => 'An account with this email already exists.'], 422);

$usedDefault = $password === '';
$finalPassword = $usedDefault ? 'password123' : $password;
$hash = password_hash($finalPassword, PASSWORD_BCRYPT);
$colors = ['#4f46e5', '#0891b2', '#059669', '#b45309', '#be185d', '#7c3aed', '#16a34a', '#4338ca'];

$stmt = $pdo->prepare('INSERT INTO users (name, email, password, role, avatar_color) VALUES (?,?,?,?,?)');
$stmt->execute([$name, $email, $hash, $role, $colors[array_rand($colors)]]);
$newId = (int)$pdo->lastInsertId();

if ($role === 'student') {
    $pdo->prepare('INSERT INTO student_profiles (user_id) VALUES (?)')->execute([$newId]);
}

logAudit($user['id'], 'admin_created_user', 'user', $newId, "$name ($role)");

$roleLabel = ['student' => 'Student', 'exec' => 'Club Executive', 'admin' => 'OCA Administrator'][$role];
$msg = "$roleLabel account created for $name." . ($usedDefault ? ' Default password: password123' : '');
jsonResponse(['ok' => true, 'message' => $msg, 'reload' => true, 'closeModal' => true]);
