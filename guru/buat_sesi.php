<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';
require __DIR__ . '/../assets/phpqrcode/qrlib.php';

if ($_SESSION['role'] != 'guru') {
    header('Location: ../login.php');
    exit;
}

$guru_id = $_SESSION['user_id'];
$message = '';
$token_baru = '';
$kelas_baru = '';

// Ambil daftar kelas milik guru
$stmt = $pdo->prepare("SELECT * FROM kelas WHERE guru_id = ?");
$stmt->execute([$guru_id]);
$daftar_kelas = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $kelas_id = $_POST['kelas_id'];
    $durasi   = $_POST['durasi'];

    // Generate token unik
    do {
        $token = strtoupper(substr(md5(uniqid(rand(), true)), 0, 6));
        $cek = $pdo->prepare("SELECT id FROM sesi_absensi WHERE token = ?");
        $cek->execute([$token]);
    } while ($cek->fetch()); // Pastikan token belum ada

    $waktu_mulai   = date('Y-m-d H:i:s');
    $waktu_selesai = date('Y-m-d H:i:s', strtotime("+$durasi minutes"));

    // Generate QR Code
    $qr_url = "http://192.168.1.5/absensi/siswa/absen.php?token=$token";
    $qr_path = __DIR__ . '/../assets/qrcodes/' . $token . '.png';

    // Pastikan folder ada
    if (!is_dir(__DIR__ . '/../assets/qrcodes')) {
        mkdir(__DIR__ . '/../assets/qrcodes', 0777, true);
    }

    QRcode::png($qr_url, $qr_path, 'M', 8, 2);

    // Simpan ke database
    $stmt = $pdo->prepare("
        INSERT INTO sesi_absensi (kelas_id, guru_id, token, qr_code, waktu_mulai, waktu_selesai, status)
        VALUES (?, ?, ?, ?, ?, ?, 'aktif')
    ");
    $stmt->execute([
        $kelas_id,
        $guru_id,
        $token,
        'assets/qrcodes/' . $token . '.png',
        $waktu_mulai,
        $waktu_selesai
    ]);

    $token_baru = $token;
    $kelas_baru = $kelas_id;
    $message    = 'success';
}

// Riwayat sesi guru ini
$riwayat = $pdo->prepare("
    SELECT s.*, k.nama_kelas, COUNT(a.id) as total_hadir
    FROM sesi_absensi s
    JOIN kelas k ON s.kelas_id = k.id
    LEFT JOIN absensi a ON s.id = a.sesi_id
    WHERE s.guru_id = ?
    GROUP BY s.id
    ORDER BY s.waktu_mulai DESC
    LIMIT 10
");
$riwayat->execute([$guru_id]);
$riwayat = $riwayat->fetchAll();
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>📋 Buat Sesi Absensi</h4>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<?php if ($message == 'success'): ?>
<!-- Tampilkan QR Code & Token -->
<div class="card border-success shadow-sm mb-4">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">✅ Sesi Berhasil Dibuat!</h5>
    </div>
    <div class="card-body text-center py-4">
        <p class="text-muted mb-2">Token Absensi:</p>
        <h1 class="display-3 fw-bold text-primary letter-spacing-3 mb-3">
            <?= $token_baru ?>
        </h1>
        <div class="mb-3">
            <img src="../assets/qrcodes/<?= $token_baru ?>.png" 
                 alt="QR Code <?= $token_baru ?>"
                 class="img-fluid border rounded p-2"
                 style="max-width:200px">
        </div>
        <p class="text-muted small">
            Bagikan token atau QR Code ini ke siswa.<br>
            Berlaku selama <strong><?= $_POST['durasi'] ?? 30 ?> menit</strong> 
            hingga <strong><?= date('H:i', strtotime($waktu_selesai)) ?></strong>
        </p>
        <div class="d-flex gap-2 justify-content-center mt-3">
            <button onclick="window.print()" class="btn btn-outline-primary">🖨️ Print QR</button>
            <a href="buat_sesi.php" class="btn btn-primary">+ Buat Sesi Baru</a>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Form Buat Sesi -->
<div class="card shadow-sm mb-4">
    <div class="card-header bg-white">
        <h6 class="mb-0">Form Buat Sesi</h6>
    </div>
    <div class="card-body">
        <?php if (empty($daftar_kelas)): ?>
        <div class="alert alert-warning">
            Belum ada kelas yang ditugaskan ke Anda. Hubungi admin.
        </div>
        <?php else: ?>
        <form method="POST">
            <div class="mb-3">
                <label class="form-label fw-bold">Pilih Kelas</label>
                <select name="kelas_id" class="form-select" required>
                    <option value="">-- Pilih Kelas --</option>
                    <?php foreach ($daftar_kelas as $kelas): ?>
                    <option value="<?= $kelas['id'] ?>">
                        <?= htmlspecialchars($kelas['nama_kelas']) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label class="form-label fw-bold">Durasi Absensi</label>
                <select name="durasi" class="form-select">
                    <option value="10">10 Menit</option>
                    <option value="15">15 Menit</option>
                    <option value="30" selected>30 Menit</option>
                    <option value="60">60 Menit</option>
                    <option value="90">90 Menit</option>
                </select>
                <div class="form-text">Siswa hanya bisa absen selama durasi ini.</div>
            </div>
            <button type="submit" class="btn btn-primary px-4">
                🚀 Generate Token & QR Code
            </button>
        </form>
        <?php endif; ?>
    </div>
</div>

<!-- Riwayat Sesi -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between">
        <h6 class="mb-0">📌 Riwayat Sesi</h6>
    </div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Kelas</th>
                    <th>Token</th>
                    <th>Waktu Mulai</th>
                    <th>Selesai</th>
                    <th>Hadir</th>
                    <th>Status</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($riwayat)): ?>
                <tr><td colspan="7" class="text-center py-4 text-muted">Belum ada sesi</td></tr>
                <?php endif; ?>
                <?php foreach ($riwayat as $row):
                    $badge = $row['status'] == 'aktif' ? 'success' : 'secondary';
                ?>
                <tr>
                    <td><?= htmlspecialchars($row['nama_kelas']) ?></td>
                    <td><strong class="text-primary"><?= $row['token'] ?></strong></td>
                    <td><?= date('d/m H:i', strtotime($row['waktu_mulai'])) ?></td>
                    <td><?= date('d/m H:i', strtotime($row['waktu_selesai'])) ?></td>
                    <td><span class="badge bg-primary"><?= $row['total_hadir'] ?></span></td>
                    <td>
    <span class="badge bg-<?= $badge ?>"><?= ucfirst($row['status']) ?></span>
    <?php if ($row['status'] == 'aktif'): 
        $sisa = strtotime($row['waktu_selesai']) - time();
        $sisa = max(0, $sisa);
    ?>
    <br>
    <small class="text-danger fw-bold" 
           data-waktu-selesai="<?= strtotime($row['waktu_selesai']) ?>"
           data-waktu-mulai="<?= strtotime($row['waktu_mulai']) ?>">
        ⏱️ <span class="timer-text">Menghitung...</span>
    </small>
    <?php endif; ?>
</td>
                    <td>
                        <?php if ($row['status'] == 'aktif'): ?>
                        <a href="tutup_sesi.php?id=<?= $row['id'] ?>" 
                           class="btn btn-sm btn-danger"
                           onclick="return confirm('Tutup sesi ini?')">Tutup</a>
                        <?php else: ?>
                        <a href="detail_sesi.php?id=<?= $row['id'] ?>" 
                           class="btn btn-sm btn-outline-primary">Detail</a>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<script>
function updateAllTimers() {
    const now = Math.floor(Date.now() / 1000);

    document.querySelectorAll('[data-waktu-selesai]').forEach(el => {
        const selesai = parseInt(el.dataset.waktuSelesai);
        const mulai   = parseInt(el.dataset.waktuMulai);
        const sisa    = selesai - now;
        const total   = selesai - mulai;

        if (sisa <= 0) {
            el.querySelector('.timer-text').textContent = 'Sesi berakhir!';
            el.classList.remove('text-danger');
            el.classList.add('text-secondary');
            return;
        }

        const menit = Math.floor(sisa / 60);
        const detik = sisa % 60;
        const persen = Math.round((sisa / total) * 100);

        let teks = '';
        if (menit > 0) teks = `${menit}m ${String(detik).padStart(2,'0')}s`;
        else           teks = `${detik}s`;

        el.querySelector('.timer-text').textContent = `${teks} (${persen}%)`;

        // Warna berdasarkan sisa waktu
        el.className = '[data-waktu-selesai] fw-bold';
        if (persen > 50)      el.classList.add('text-success');
        else if (persen > 20) el.classList.add('text-warning');
        else                  el.classList.add('text-danger');
    });
}

updateAllTimers();
setInterval(updateAllTimers, 1000);
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>