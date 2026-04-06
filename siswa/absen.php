<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SESSION['role'] != 'siswa') {
    header('Location: ../login.php');
    exit;
}

$siswa_id = $_SESSION['user_id'];
$message  = '';
$type     = '';
$token    = $_GET['token'] ?? '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $token = strtoupper(trim($_POST['token']));
}

if ($token) {
    $sekarang = date('Y-m-d H:i:s');

    // Cek token valid & masih aktif
    $stmt = $pdo->prepare("
        SELECT s.*, k.nama_kelas 
        FROM sesi_absensi s
        JOIN kelas k ON s.kelas_id = k.id
        WHERE s.token = ? AND s.status = 'aktif' AND s.waktu_selesai >= ?
    ");
    $stmt->execute([$token, $sekarang]);
    $sesi = $stmt->fetch();

    if (!$sesi) {
        $message = "❌ Token tidak valid atau sudah expired!";
        $type    = "danger";
    } else {
        // Cek sudah absen belum
        $cek = $pdo->prepare("SELECT id FROM absensi WHERE sesi_id = ? AND siswa_id = ?");
        $cek->execute([$sesi['id'], $siswa_id]);

        if ($cek->fetch()) {
            $message = "⚠️ Anda sudah melakukan absensi untuk sesi ini!";
            $type    = "warning";
        } else {
            // Tentukan status hadir/terlambat
            $toleransi = strtotime($sesi['waktu_mulai']) + (15 * 60);
            $status    = (strtotime($sekarang) <= $toleransi) ? 'hadir' : 'terlambat';

            // Simpan absensi
            $ins = $pdo->prepare("
                INSERT INTO absensi (sesi_id, siswa_id, waktu_absen, status) 
                VALUES (?, ?, ?, ?)
            ");
            $ins->execute([$sesi['id'], $siswa_id, $sekarang, $status]);

            $message = "✅ Absensi berhasil! Kelas: <strong>" . htmlspecialchars($sesi['nama_kelas']) . "</strong> | Status: <strong>" . ucfirst($status) . "</strong>";
            $type    = "success";
            $token   = ''; // Reset token setelah berhasil
        }
    }
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<!-- Cek sesi aktif untuk countdown -->
<?php
$sesi_countdown = $pdo->query("
    SELECT token, waktu_mulai, waktu_selesai, 
           TIMESTAMPDIFF(SECOND, NOW(), waktu_selesai) as sisa_detik
    FROM sesi_absensi
    WHERE status = 'aktif' AND waktu_selesai >= NOW()
    ORDER BY waktu_selesai ASC
    LIMIT 1
")->fetch();
?>

<?php if ($sesi_countdown): ?>
<div class="card shadow-sm mb-4 border-0" id="countdownCard">
    <div class="card-body p-0 overflow-hidden" style="border-radius:12px">
        <!-- Header -->
        <div class="bg-primary text-white text-center py-2">
            <small class="fw-bold">🟢 SESI ABSENSI SEDANG BERLANGSUNG</small>
        </div>
        <!-- Countdown -->
        <div class="text-center py-4 px-3">
            <p class="text-muted mb-2 small">Sesi berakhir dalam:</p>
            <div class="d-flex justify-content-center gap-3 mb-3" id="countdownDisplay">
                <div class="text-center">
                    <div class="display-4 fw-bold text-primary" id="cd-jam">00</div>
                    <div class="small text-muted">Jam</div>
                </div>
                <div class="display-4 fw-bold text-muted mt-1">:</div>
                <div class="text-center">
                    <div class="display-4 fw-bold text-primary" id="cd-menit">00</div>
                    <div class="small text-muted">Menit</div>
                </div>
                <div class="display-4 fw-bold text-muted mt-1">:</div>
                <div class="text-center">
                    <div class="display-4 fw-bold text-danger" id="cd-detik">00</div>
                    <div class="small text-muted">Detik</div>
                </div>
            </div>
            <!-- Progress bar -->
            <?php
            $total_detik = strtotime($sesi_countdown['waktu_selesai']) - strtotime($sesi_countdown['waktu_mulai']);
            $sisa_detik  = max(0, $sesi_countdown['sisa_detik']);
            $persen_sisa = round(($sisa_detik / $total_detik) * 100);
            $bar_color   = $persen_sisa > 50 ? 'success' : ($persen_sisa > 20 ? 'warning' : 'danger');
            ?>
            <div class="progress mx-3 mb-2" style="height:10px; border-radius:10px">
                <div class="progress-bar bg-<?= $bar_color ?> progress-bar-striped progress-bar-animated"
                     id="progressBar"
                     style="width: <?= $persen_sisa ?>%; border-radius:10px"
                     role="progressbar">
                </div>
            </div>
            <small class="text-muted">
                Token aktif: <strong class="text-primary" style="letter-spacing:3px"><?= $sesi_countdown['token'] ?></strong>
            </small>
        </div>
    </div>
</div>

<script>
// Countdown realtime
let sisaDetik = <?= $sisa_detik ?>;
let totalDetik = <?= $total_detik ?>;

function updateCountdown() {
    if (sisaDetik <= 0) {
        // Sesi habis
        document.getElementById('countdownCard').innerHTML = `
            <div class="card-body text-center py-4">
                <div style="font-size:48px">⏰</div>
                <h5 class="text-danger mt-2">Sesi Absensi Sudah Berakhir!</h5>
                <p class="text-muted small">Hubungi guru untuk membuka sesi baru.</p>
            </div>
        `;
        return;
    }

    const jam    = Math.floor(sisaDetik / 3600);
    const menit  = Math.floor((sisaDetik % 3600) / 60);
    const detik  = sisaDetik % 60;

    document.getElementById('cd-jam').textContent    = String(jam).padStart(2, '0');
    document.getElementById('cd-menit').textContent  = String(menit).padStart(2, '0');
    document.getElementById('cd-detik').textContent  = String(detik).padStart(2, '0');

    // Update progress bar
    const persen = Math.round((sisaDetik / totalDetik) * 100);
    const bar    = document.getElementById('progressBar');
    bar.style.width = persen + '%';

    // Ganti warna progress bar
    bar.className = 'progress-bar progress-bar-striped progress-bar-animated border-radius-10px';
    if (persen > 50)       bar.classList.add('bg-success');
    else if (persen > 20)  bar.classList.add('bg-warning');
    else                   bar.classList.add('bg-danger');

    // Efek kedip kalau tinggal 60 detik
    if (sisaDetik <= 60) {
        document.getElementById('cd-detik').style.animation = 'blink 0.5s infinite';
        document.getElementById('countdownCard').style.borderColor = '#dc3545';
        document.getElementById('countdownCard').style.border = '2px solid #dc3545';
    }

    sisaDetik--;
}

// Jalankan setiap 1 detik
updateCountdown();
setInterval(updateCountdown, 1000);
</script>

<style>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.2; }
}
</style>
<?php endif; ?><!-- Cek sesi aktif untuk countdown -->
<?php
$sesi_countdown = $pdo->query("
    SELECT token, waktu_mulai, waktu_selesai, 
           TIMESTAMPDIFF(SECOND, NOW(), waktu_selesai) as sisa_detik
    FROM sesi_absensi
    WHERE status = 'aktif' AND waktu_selesai >= NOW()
    ORDER BY waktu_selesai ASC
    LIMIT 1
")->fetch();
?>

<?php if ($sesi_countdown): ?>
<div class="card shadow-sm mb-4 border-0" id="countdownCard">
    <div class="card-body p-0 overflow-hidden" style="border-radius:12px">
        <!-- Header -->
        <div class="bg-primary text-white text-center py-2">
            <small class="fw-bold">🟢 SESI ABSENSI SEDANG BERLANGSUNG</small>
        </div>
        <!-- Countdown -->
        <div class="text-center py-4 px-3">
            <p class="text-muted mb-2 small">Sesi berakhir dalam:</p>
            <div class="d-flex justify-content-center gap-3 mb-3" id="countdownDisplay">
                <div class="text-center">
                    <div class="display-4 fw-bold text-primary" id="cd-jam">00</div>
                    <div class="small text-muted">Jam</div>
                </div>
                <div class="display-4 fw-bold text-muted mt-1">:</div>
                <div class="text-center">
                    <div class="display-4 fw-bold text-primary" id="cd-menit">00</div>
                    <div class="small text-muted">Menit</div>
                </div>
                <div class="display-4 fw-bold text-muted mt-1">:</div>
                <div class="text-center">
                    <div class="display-4 fw-bold text-danger" id="cd-detik">00</div>
                    <div class="small text-muted">Detik</div>
                </div>
            </div>
            <!-- Progress bar -->
            <?php
            $total_detik = strtotime($sesi_countdown['waktu_selesai']) - strtotime($sesi_countdown['waktu_mulai']);
            $sisa_detik  = max(0, $sesi_countdown['sisa_detik']);
            $persen_sisa = round(($sisa_detik / $total_detik) * 100);
            $bar_color   = $persen_sisa > 50 ? 'success' : ($persen_sisa > 20 ? 'warning' : 'danger');
            ?>
            <div class="progress mx-3 mb-2" style="height:10px; border-radius:10px">
                <div class="progress-bar bg-<?= $bar_color ?> progress-bar-striped progress-bar-animated"
                     id="progressBar"
                     style="width: <?= $persen_sisa ?>%; border-radius:10px"
                     role="progressbar">
                </div>
            </div>
            <small class="text-muted">
                Token aktif: <strong class="text-primary" style="letter-spacing:3px"><?= $sesi_countdown['token'] ?></strong>
            </small>
        </div>
    </div>
</div>

<script>
// Countdown realtime
let sisaDetik = <?= $sisa_detik ?>;
let totalDetik = <?= $total_detik ?>;

function updateCountdown() {
    if (sisaDetik <= 0) {
        // Sesi habis
        document.getElementById('countdownCard').innerHTML = `
            <div class="card-body text-center py-4">
                <div style="font-size:48px">⏰</div>
                <h5 class="text-danger mt-2">Sesi Absensi Sudah Berakhir!</h5>
                <p class="text-muted small">Hubungi guru untuk membuka sesi baru.</p>
            </div>
        `;
        return;
    }

    const jam    = Math.floor(sisaDetik / 3600);
    const menit  = Math.floor((sisaDetik % 3600) / 60);
    const detik  = sisaDetik % 60;

    document.getElementById('cd-jam').textContent    = String(jam).padStart(2, '0');
    document.getElementById('cd-menit').textContent  = String(menit).padStart(2, '0');
    document.getElementById('cd-detik').textContent  = String(detik).padStart(2, '0');

    // Update progress bar
    const persen = Math.round((sisaDetik / totalDetik) * 100);
    const bar    = document.getElementById('progressBar');
    bar.style.width = persen + '%';

    // Ganti warna progress bar
    bar.className = 'progress-bar progress-bar-striped progress-bar-animated border-radius-10px';
    if (persen > 50)       bar.classList.add('bg-success');
    else if (persen > 20)  bar.classList.add('bg-warning');
    else                   bar.classList.add('bg-danger');

    // Efek kedip kalau tinggal 60 detik
    if (sisaDetik <= 60) {
        document.getElementById('cd-detik').style.animation = 'blink 0.5s infinite';
        document.getElementById('countdownCard').style.borderColor = '#dc3545';
        document.getElementById('countdownCard').style.border = '2px solid #dc3545';
    }

    sisaDetik--;
}

// Jalankan setiap 1 detik
updateCountdown();
setInterval(updateCountdown, 1000);
</script>

<style>
@keyframes blink {
    0%, 100% { opacity: 1; }
    50%       { opacity: 0.2; }
}
</style>
<?php endif; ?>
<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>✅ Form Absensi</h4>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $type ?> alert-dismissible fade show">
    <?= $message ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<!-- Form Input Token -->
<div class="card shadow-sm mb-4">
    <div class="card-body py-4">
        <h5 class="text-center mb-4">Masukkan Token Absensi</h5>
        <form method="POST">
            <div class="mb-3">
                <input type="text" 
                       name="token" 
                       class="form-control form-control-lg text-center fw-bold"
                       style="font-size:2rem; letter-spacing:8px"
                       placeholder="ABC123"
                       maxlength="6"
                       value="<?= htmlspecialchars($token) ?>"
                       autocomplete="off"
                       autofocus
                       required>
                <div class="form-text text-center">Token terdiri dari 6 karakter, diberikan oleh guru</div>
            </div>
            <div class="d-grid">
                <button type="submit" class="btn btn-primary btn-lg">
                    ✅ Absen Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Info Sesi Aktif -->
<?php
$sesi_aktif = $pdo->query("
    SELECT s.token, s.waktu_selesai, k.nama_kelas
    FROM sesi_absensi s
    JOIN kelas k ON s.kelas_id = k.id
    WHERE s.status = 'aktif' AND s.waktu_selesai >= NOW()
    LIMIT 5
")->fetchAll();
?>
<?php if (!empty($sesi_aktif)): ?>
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h6 class="mb-0">🟢 Sesi Absensi Aktif Saat Ini</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kelas</th>
                    <th>Token</th>
                    <th>Berlaku Hingga</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($sesi_aktif as $sesi): ?>
                <tr>
                    <td><?= htmlspecialchars($sesi['nama_kelas']) ?></td>
                    <td><strong class="text-primary" style="letter-spacing:3px"><?= $sesi['token'] ?></strong></td>
                    <td><?= date('H:i', strtotime($sesi['waktu_selesai'])) ?></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" 
                                onclick="document.querySelector('[name=token]').value='<?= $sesi['token'] ?>'">
                            Gunakan Token
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- Riwayat Absensi Hari Ini -->
<?php
$riwayat_hari_ini = $pdo->prepare("
    SELECT a.status, a.waktu_absen, k.nama_kelas
    FROM absensi a
    JOIN sesi_absensi s ON a.sesi_id = s.id
    JOIN kelas k ON s.kelas_id = k.id
    WHERE a.siswa_id = ? AND DATE(a.waktu_absen) = CURDATE()
    ORDER BY a.waktu_absen DESC
");
$riwayat_hari_ini->execute([$siswa_id]);
$riwayat_hari_ini = $riwayat_hari_ini->fetchAll();
?>
<?php if (!empty($riwayat_hari_ini)): ?>
<div class="card shadow-sm mt-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">📌 Absensi Hari Ini</h6>
    </div>
    <div class="card-body p-0">
        <table class="table mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kelas</th>
                    <th>Waktu</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($riwayat_hari_ini as $row):
                    $badge = $row['status'] == 'hadir' ? 'success' : ($row['status'] == 'terlambat' ? 'warning' : 'danger');
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><?= date('H:i', strtotime($row['waktu_absen'])) ?></td>
                    <td><span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php include __DIR__ . '/../includes/footer.php'; ?>