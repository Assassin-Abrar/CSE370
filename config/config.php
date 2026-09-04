<?php
// Core app config — session start, error display, base paths.
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

error_reporting(E_ALL);
ini_set('display_errors', '1');

define('APP_NAME', 'BRACU Club Management System');

// Base URL relative to web root, works whether app sits at / or /F_shitz/
$scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME']));
$root = $scriptDir;
// Normalize when included from a subfolder (auth/, student/, exec/, admin/, api/)
foreach (['/auth', '/student', '/exec', '/admin', '/api'] as $sub) {
    if (substr($root, -strlen($sub)) === $sub) {
        $root = substr($root, 0, -strlen($sub));
        break;
    }
}
if ($root === '' ) $root = '/';
if (substr($root, -1) !== '/') $root .= '/';
define('BASE_URL', $root);

define('UPLOAD_DIR_CV', __DIR__ . '/../uploads/cv/');
define('UPLOAD_DIR_LOGOS', __DIR__ . '/../uploads/logos/');
define('UPLOAD_URL_CV', BASE_URL . 'uploads/cv/');
define('UPLOAD_URL_LOGOS', BASE_URL . 'uploads/logos/');
