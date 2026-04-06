<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

// Statistik
$total_siswa  = $pdo->query("SELECT COUNT(*) FROM users WHERE role='siswa'")->fetchColumn();
$total_guru   = $pdo->query("SELECT COUNT(*) FROM users WHERE role='guru'")->fetchColumn();
$total_kelas  = $pdo->query("SELECT COUNT(*) FROM kelas")->fetchColumn();
$total_sesi   = $pdo->query("SELECT COUNT(*) FROM sesi_absensi WHERE DATE(waktu_mulai) = CURDATE()")->fetchColumn();
$sesi_aktif   = $pdo->query("SELECT COUNT(*) FROM sesi_absensi WHERE status='aktif'")->fetchColumn();
$total_absensi = $pdo->query("SELECT COUNT(*) FROM absensi WHERE DATE(waktu_absen) = CURDATE()")->fetchColumn();

// Absensi terbaru hari ini
$absensi_hari_ini = $pdo->query("
    SELECT u.nama, a.status, a.waktu_absen, k.nama_kelas
    FROM absensi a
    JOIN users u ON a.siswa_id = u.id
    JOIN sesi_absensi s ON a.sesi_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE DATE(a.waktu_absen) = CURDATE()
    ORDER BY a.waktu_absen DESC
    LIMIT 10
")->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<h4 class="mb-4">👋 Selamat datang, <?= htmlspecialchars($_SESSION['nama']) ?>!</h4>

<!-- Statistik Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-primary bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">👨‍🎓</span>
                </div>
                <div>
                    <div class="text-muted small">Total Siswa</div>
                    <div class="fs-3 fw-bold text-primary"><?= $total_siswa ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-success bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">👨‍🏫</span>
                </div>
                <div>
                    <div class="text-muted small">Total Guru</div>
                    <div class="fs-3 fw-bold text-success"><?= $total_guru ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-info bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">🏫</span>
                </div>
                <div>
                    <div class="text-muted small">Total Kelas</div>
                    <div class="fs-3 fw-bold text-info"><?= $total_kelas ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-warning bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">📋</span>
                </div>
                <div>
                    <div class="text-muted small">Sesi Hari Ini</div>
                    <div class="fs-3 fw-bold text-warning"><?= $total_sesi ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-danger bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">🟢</span>
                </div>
                <div>
                    <div class="text-muted small">Sesi Aktif</div>
                    <div class="fs-3 fw-bold text-danger"><?= $sesi_aktif ?></div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body d-flex align-items-center gap-3">
                <div class="bg-secondary bg-opacity-10 rounded-3 p-3">
                    <span style="font-size:28px">✅</span>
                </div>
                <div>
                    <div class="text-muted small">Absen Hari Ini</div>
                    <div class="fs-3 fw-bold text-secondary"><?= $total_absensi ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Absensi Terbaru -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h6 class="mb-0">📌 Absensi Terbaru Hari Ini</h6>
<div class="d-flex gap-2 mb-3">
    <a href="kelola_user.php" class="btn btn-primary">👥 Kelola User</a>
    <a href="rekap.php" class="btn btn-outline-primary">📊 Rekap Absensi</a>
</div>
        <a href="rekap.php" class="btn btn-sm btn-outline-primary">Lihat Semua</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Nama Siswa</th>
                    <th>Kelas</th>
                    <th>Waktu Absen</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($absensi_hari_ini)): ?>
                <tr><td colspan="4" class="text-center py-4 text-muted">Belum ada absensi hari ini</td></tr>
                <?php endif; ?>
                <?php foreach ($absensi_hari_ini as $row): 
                    $badge = $row['status'] == 'hadir' ? 'success' : ($row['status'] == 'terlambat' ? 'warning' : 'danger');
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><?= date('H:i', strtotime($row['waktu_absen'])) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>