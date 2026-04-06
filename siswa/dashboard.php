<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SESSION['role'] != 'siswa') {
    header('Location: ../login.php');
    exit;
}

$siswa_id = $_SESSION['user_id'];

// Statistik siswa
$stmt = $pdo->prepare("
    SELECT
        COUNT(CASE WHEN a.status = 'hadir'     THEN 1 END) AS hadir,
        COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) AS terlambat,
        COUNT(CASE WHEN a.status = 'alpha'     THEN 1 END) AS alpha,
        COUNT(a.id) AS total,
        ROUND(
            COUNT(CASE WHEN a.status IN ('hadir','terlambat') THEN 1 END) * 100.0
            / NULLIF(COUNT(a.id), 0), 1
        ) AS persen
    FROM absensi a
    WHERE a.siswa_id = ?
");
$stmt->execute([$siswa_id]);
$stats = $stmt->fetch();

// Riwayat absensi
$riwayat = $pdo->prepare("
    SELECT a.status, a.waktu_absen, k.nama_kelas
    FROM absensi a
    JOIN sesi_absensi s ON a.sesi_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE a.siswa_id = ?
    ORDER BY a.waktu_absen DESC
    LIMIT 10
");
$riwayat->execute([$siswa_id]);
$riwayat = $riwayat->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h4 class="mb-4">👋 Halo, <?= htmlspecialchars($_SESSION['nama']) ?>!</h4>

<!-- Statistik -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="fs-2 fw-bold text-success"><?= $stats['hadir'] ?? 0 ?></div>
                <div class="text-muted small">Hadir</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="fs-2 fw-bold text-warning"><?= $stats['terlambat'] ?? 0 ?></div>
                <div class="text-muted small">Terlambat</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="fs-2 fw-bold text-danger"><?= $stats['alpha'] ?? 0 ?></div>
                <div class="text-muted small">Alpha</div>
            </div>
        </div>
    </div>
    <div class="col-6 col-md-3">
        <div class="card border-0 shadow-sm text-center">
            <div class="card-body">
                <div class="fs-2 fw-bold text-primary"><?= $stats['persen'] ?? 0 ?>%</div>
                <div class="text-muted small">Kehadiran</div>
            </div>
        </div>
    </div>
</div>

<!-- Tombol Absen -->
<div class="card border-primary shadow-sm mb-4">
    <div class="card-body text-center py-4">
        <h5>Mau absen sekarang?</h5>
        <p class="text-muted">Masukkan token yang diberikan guru</p>
        <a href="absen.php" class="btn btn-primary btn-lg px-5">✅ Absen Sekarang</a>
    </div>
</div>

<!-- Riwayat Absensi -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">📌 Riwayat Absensi Terakhir</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kelas</th>
                    <th>Waktu Absen</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat)): ?>
                <tr><td colspan="3" class="text-center py-4 text-muted">Belum ada riwayat absensi</td></tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $row): 
                    $badge = $row['status'] == 'hadir' ? 'success' : ($row['status'] == 'terlambat' ? 'warning' : 'danger');
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['waktu_absen'])) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>