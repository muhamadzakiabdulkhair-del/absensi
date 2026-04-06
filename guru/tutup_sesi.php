<?php
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}

$id      = $_GET['id'] ?? 0;
$guru_id = $_SESSION['user_id'];

// Pastikan sesi milik guru ini
$stmt = $pdo->prepare("UPDATE sesi_absensi SET status = 'selesai' WHERE id = ? AND guru_id = ?");
$stmt->execute([$id, $guru_id]);

header('Location: buat_sesi.php');
exit;
?>