<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}

$guru_id = $_SESSION['user_id'];

// Statistik guru
$total_kelas = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE guru_id = ?");
$total_kelas->execute([$guru_id]);
$total_kelas = $total_kelas->fetchColumn();

$total_sesi = $pdo->prepare("SELECT COUNT(*) FROM sesi_absensi WHERE guru_id = ?");
$total_sesi->execute([$guru_id]);
$total_sesi = $total_sesi->fetchColumn();

$sesi_aktif = $pdo->prepare("SELECT COUNT(*) FROM sesi_absensi WHERE guru_id = ? AND status = 'aktif'");
$sesi_aktif->execute([$guru_id]);
$sesi_aktif = $sesi_aktif->fetchColumn();

// Riwayat sesi terbaru
$riwayat = $pdo->prepare("
    SELECT s.*, k.nama_kelas,
        COUNT(a.id) as total_hadir
    FROM sesi_absensi s
    JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN absensi a ON s.id = a.sesi_id
    WHERE s.guru_id = ?
    GROUP BY s.id
    ORDER BY s.waktu_mulai DESC
    LIMIT 8
");
$riwayat->execute([$guru_id]);
$riwayat = $riwayat->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h4 class="mb-4">👋 Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</h4>

<!-- Statistik -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">🏫</span>
                </div>
                <div>
                    <div class="text-muted small">Kelas Saya</div>
                    <div class="fs-3 fw-bold text-primary"><?= $total_kelas ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">📋</span>
                </div>
                <div>
                    <div class="text-muted small">Total Sesi</div>
                    <div class="fs-3 fw-bold text-success"><?= $total_sesi ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">🟢</span>
                </div>
                <div>
                    <div class="text-muted small">Sesi Aktif</div>
                    <div class="fs-3 fw-bold text-warning"><?= $sesi_aktif ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tombol Aksi -->
<div class="d-flex gap-2 mb-4">
    <a href="buat_sesi.php" class="btn btn-primary">+ Buat Sesi Absensi</a>
</div>

<!-- Riwayat Sesi -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">📌 Riwayat Sesi Terbaru</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kelas</th>
                    <th>Token</th>
                    <th>Waktu Mulai</th>
                    <th>Waktu Selesai</th>
                    <th>Hadir</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat)): ?>
                <tr><td colspan="6" class="text-center py-4 text-muted">Belum ada sesi dibuat</td></tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $row): 
                    $badge = $row['status'] == 'aktif' ? 'success' : 'secondary';
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><strong><?= $row['token'] ?></strong></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['waktu_mulai'])) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['waktu_selesai'])) ?></td>
                    <td><span class="badge bg-primary"><?= $row['total_hadir'] ?> siswa</span></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>