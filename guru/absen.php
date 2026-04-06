// siswa/absen.php
session_start();
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/footer.php';

$token = $_GET['token'] ?? $_POST['token'];
$siswa_id = $_SESSION['user_id'];
$sekarang = date('Y-m-d H:i:s');

// Cek token valid & masih aktif
$stmt = $pdo->prepare("SELECT * FROM sesi_absensi 
    WHERE token = ? AND status = 'aktif' AND waktu_selesai >= ?");
$stmt->execute([$token, $sekarang]);
$sesi = $stmt->fetch();

if (!$sesi) {
    die("Token tidak valid atau sudah expired!");
}

// Cek sudah absen belum
$cek = $pdo->prepare("SELECT id FROM absensi WHERE sesi_id = ? AND siswa_id = ?");
$cek->execute([$sesi['id'], $siswa_id]);
if ($cek->fetch()) {
    die("Anda sudah melakukan absensi!");
}

// Tentukan status (hadir/terlambat)
$toleransi = strtotime($sesi['waktu_mulai']) + (15 * 60); // +15 menit
$status = (strtotime($sekarang) <= $toleransi) ? 'hadir' : 'terlambat';

// Simpan absensi
$stmt = $pdo->prepare("INSERT INTO absensi (sesi_id, siswa_id, waktu_absen, status) VALUES (?, ?, ?, ?)");
$stmt->execute([$sesi['id'], $siswa_id, $sekarang, $status]);

echo "Absensi berhasil! Status: $status";