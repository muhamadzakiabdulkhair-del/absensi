<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="bg-light">
<nav class="navbar navbar-expand-lg navbar-dark bg-primary px-3 shadow-sm">
    <span class="navbar-brand fw-bold">📋 Absensi Online</span>
    <div class="ms-auto d-flex align-items-center gap-3">
        <span class="text-white small">👤 <?= htmlspecialchars($_SESSION['nama'] ?? '') ?></span>
        <span class="badge bg-light text-primary"><?= ucfirst($_SESSION['role'] ?? '') ?></span>
        <a href="<?= str_repeat('../', substr_count($_SERVER['PHP_SELF'], '/') - 2) ?>logout.php"
           class="btn btn-sm btn-outline-light">Logout</a>
    </div>
</nav>
<div class="container mt-4 mb-5">