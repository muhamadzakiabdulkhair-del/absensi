<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$filter_bulan = $_GET['bulan'] ?? date('Y-m');
$filter_kelas = $_GET['kelas_id'] ?? '';

$kelas_list = $pdo->query("SELECT * FROM kelas ORDER BY nama_kelas")->fetchAll();

$where  = "WHERE u.role = 'siswa'";
$params = [$filter_bulan];

if ($filter_kelas) {
    $where .= " AND EXISTS (
        SELECT 1 FROM absensi ab2
        JOIN sesi_absensi s2 ON ab2.sesi_id = s2.id
        WHERE ab2.siswa_id = u.id AND s2.kelas_id = ?
    )";
    $params[] = $filter_kelas;
}

$stmt = $pdo->prepare("
    SELECT u.id, u.nama,
        COUNT(CASE WHEN a.status = 'hadir'     THEN 1 END) AS hadir,
        COUNT(CASE WHEN a.status = 'terlambat' THEN 1 END) AS terlambat,
        COUNT(CASE WHEN a.status = 'alpha'     THEN 1 END) AS alpha,
        COUNT(a.id) AS total,
        ROUND(
            COUNT(CASE WHEN a.status IN ('hadir','terlambat') THEN 1 END) * 100.0
            / NULLIF(COUNT(a.id), 0), 1
        ) AS persen_hadir
    FROM users u
    LEFT JOIN absensi a ON u.id = a.siswa_id
        AND DATE_FORMAT(a.waktu_absen, '%Y-%m') = ?
    $where
    GROUP BY u.id, u.nama
    ORDER BY u.nama
");
$stmt->execute($params);
$rekap = $stmt->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>📊 Rekap Kehadiran</h4>
    <a href="../exports/export_excel.php?bulan=<?= $filter_bulan ?>&kelas_id=<?= $filter_kelas ?>"
       class="btn btn-success">
        ⬇ Export Excel
    </a>
</div>

<!-- Filter -->
<div class="card shadow-sm mb-4">
    <div class="card-body">
        <form method="GET" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label class="form-label fw-bold">Bulan</label>
                <input type="month" name="bulan" class="form-control" value="<?= $filter_bulan ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label fw-bold">Kelas</label>
                <select name="kelas_id" class="form-select">
                    <option value="">-- Semua Kelas --</option>
                    <?php foreach ($kelas_list as $k): ?>
                    <option value="<?= $k['id'] ?>" <?= $filter_kelas == $k['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($k['nama_kelas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100">🔍 Filter</button>
            </div>
            <div class="col-md-2">
                <a href="rekap.php" class="btn btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan -->
<?php
$total_hadir     = array_sum(array_column($rekap, 'hadir'));
$total_terlambat = array_sum(array_column($rekap, 'terlambat'));
$total_alpha     = array_sum(array_column($rekap, 'alpha'));
?>
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="card border-0 bg-success bg-opacity-10 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold text-success"><?= $total_hadir ?></div>
                <div class="text-muted">Total Hadir</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 bg-warning bg-opacity-10 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold text-warning"><?= $total_terlambat ?></div>
                <div class="text-muted">Total Terlambat</div>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 bg-danger bg-opacity-10 shadow-sm">
            <div class="card-body text-center">
                <div class="fs-2 fw-bold text-danger"><?= $total_alpha ?></div>
                <div class="text-muted">Total Alpha</div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Rekap -->
<div class="card shadow-sm">
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-dark">
                <tr>
                    <th>#</th>
                    <th>Nama Siswa</th>
                    <th class="text-center">Hadir</th>
                    <th class="text-center">Terlambat</th>
                    <th class="text-center">Alpha</th>
                    <th class="text-center">Total Sesi</th>
                    <th class="text-center">% Kehadiran</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($rekap)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Tidak ada data</td></tr>
                <?php endif; ?>
                <?php foreach ($rekap as $i => $row):
                    $persen = $row['persen_hadir'] ?? 0;
                    $badge  = $persen >= 80 ? 'success' : ($persen >= 60 ? 'warning' : 'danger');
                ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($row['nama']) ?></td>
                    <td class="text-center"><strong class="text-success"><?= $row['hadir'] ?></strong></td>
                    <td class="text-center"><strong class="text-warning"><?= $row['terlambat'] ?></strong></td>
                    <td class="text-center"><strong class="text-danger"><?= $row['alpha'] ?></strong></td>
                    <td class="text-center"><?= $row['total'] ?></td>
                    <td class="text-center">
                        <span class="badge bg-<?= $badge ?> px-3"><?= $persen ?>%</span>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>