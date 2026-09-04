<?php
// Expects: $pageTitle (string). Assumes config/auth/functions already loaded by the caller.
?><!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<script>(function(){try{var t=localStorage.getItem('bracu_theme');if(t==='dark'||t==='light'){document.documentElement.setAttribute('data-theme',t);}else if(window.matchMedia('(prefers-color-scheme: dark)').matches){document.documentElement.setAttribute('data-theme','dark');}}catch(e){}})();</script>
<title><?= e($pageTitle ?? APP_NAME) ?> · <?= APP_NAME ?></title>
<?php if (isLoggedIn()): ?><meta name="csrf-token" content="<?= e(csrfToken()) ?>"><?php endif; ?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="<?= assetUrl('assets/css/style.css') ?>">
<link rel="stylesheet" href="<?= assetUrl('assets/css/pages.css') ?>">
<link rel="icon" href="data:image/svg+xml,<svg xmlns=%22http://www.w3.org/2000/svg%22 viewBox=%220 0 100 100%22><rect width=%22100%22 height=%22100%22 rx=%2222%22 fill=%22%234f46e5%22/><text x=%2250%22 y=%2266%22 font-size=%2256%22 font-family=%22Arial%22 fill=%22white%22 text-anchor=%22middle%22>B</text></svg>">
</head>
