<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';

// API responses must ALWAYS be valid JSON. Never let a stray PHP warning/notice
// or an uncaught error/exception leak raw HTML into the body — that breaks
// fetch().json() on the client with a generic "Unexpected server response."
// Everything is still logged server-side so real bugs remain diagnosable.
ini_set('display_errors', '0');
error_reporting(E_ALL);
header('Content-Type: application/json');

function apiSendServerError(string $logPrefix, string $detail): void {
    error_log($logPrefix . ': ' . $detail);
    if (!headers_sent()) {
        http_response_code(500);
        header('Content-Type: application/json');
    }
    echo json_encode(['ok' => false, 'message' => 'A server error occurred. Please try again.']);
    exit;
}

set_error_handler(function ($severity, $message, $file, $line) {
    if (!(error_reporting() & $severity)) return false;
    if (in_array($severity, [E_WARNING, E_USER_WARNING, E_ERROR, E_USER_ERROR, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        apiSendServerError('PHP error', "$message in $file on line $line");
    }
    error_log("PHP notice: $message in $file on line $line");
    return true;
});

set_exception_handler(function (Throwable $e) {
    apiSendServerError('Uncaught exception', $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
});

register_shutdown_function(function () {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        apiSendServerError('Fatal error', $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
    }
});

function apiRequireRole(array $roles): array {
    if (!isLoggedIn()) jsonResponse(['ok' => false, 'message' => 'Please log in to continue.'], 401);
    $u = currentUser();
    if (!$u || $u['status'] !== 'active') jsonResponse(['ok' => false, 'message' => 'Your account is inactive.'], 403);
    if (!in_array($u['role'], $roles, true)) jsonResponse(['ok' => false, 'message' => 'You do not have permission to do this.'], 403);
    return $u;
}

if (!isPost()) jsonResponse(['ok' => false, 'message' => 'Invalid request method.'], 405);
