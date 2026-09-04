<?php
require_once __DIR__ . '/../includes/api_bootstrap.php';
$user = apiRequireRole(['student']);
requireCsrfOrFail();

if (empty($_FILES['cv']) || $_FILES['cv']['error'] !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'message' => 'Please choose a file to upload.'], 422);
}
$ext = strtolower(pathinfo($_FILES['cv']['name'], PATHINFO_EXTENSION));
if (!in_array($ext, ['pdf', 'doc', 'docx'], true)) jsonResponse(['ok' => false, 'message' => 'CV must be a PDF, DOC or DOCX file.'], 422);
if ($_FILES['cv']['size'] > 5 * 1024 * 1024) jsonResponse(['ok' => false, 'message' => 'File must be under 5MB.'], 422);

if (!is_dir(UPLOAD_DIR_CV)) mkdir(UPLOAD_DIR_CV, 0777, true);
$fname = 'cv_' . $user['id'] . '_' . time() . '.' . $ext;
if (!move_uploaded_file($_FILES['cv']['tmp_name'], UPLOAD_DIR_CV . $fname)) {
    jsonResponse(['ok' => false, 'message' => 'Upload failed. Please try again.'], 500);
}

$pdo = db();
$stmt = $pdo->prepare('SELECT user_id FROM student_profiles WHERE user_id = ?');
$stmt->execute([$user['id']]);
if ($stmt->fetch()) {
    $pdo->prepare('UPDATE student_profiles SET cv_path = ? WHERE user_id = ?')->execute(['uploads/cv/' . $fname, $user['id']]);
} else {
    $pdo->prepare('INSERT INTO student_profiles (user_id, cv_path) VALUES (?,?)')->execute([$user['id'], 'uploads/cv/' . $fname]);
}

jsonResponse(['ok' => true, 'message' => 'CV uploaded.', 'reload' => true]);
