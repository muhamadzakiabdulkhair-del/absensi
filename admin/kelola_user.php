<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
require __DIR__ . '/../includes/auth.php';
require __DIR__ . '/../config/database.php';

if ($_SESSION['role'] != 'admin') {
    header('Location: ../login.php');
    exit;
}

$message = '';
$type    = '';

// ===================== PROSES FORM =====================
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $action = $_POST['action'] ?? '';

    // TAMBAH USER
    if ($action == 'tambah') {
        $nama     = trim($_POST['nama']);
        $email    = trim($_POST['email']);
        $password = $_POST['password'];
        $role     = $_POST['role'];

        // Cek email sudah ada
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $cek->execute([$email]);

        if ($cek->fetch()) {
            $message = "Email sudah digunakan!";
            $type    = "danger";
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO users (nama, email, password, role) VALUES (?, ?, ?, ?)");
            $stmt->execute([$nama, $email, $hash, $role]);

            // Kalau role guru, buat kelas otomatis jika diisi
            if ($role == 'guru' && !empty($_POST['nama_kelas'])) {
                $guru_id = $pdo->lastInsertId();
                $stmt2   = $pdo->prepare("INSERT INTO kelas (nama_kelas, guru_id) VALUES (?, ?)");
                $stmt2->execute([trim($_POST['nama_kelas']), $guru_id]);
            }

            $message = "User berhasil ditambahkan!";
            $type    = "success";
        }
    }

    // EDIT USER
    if ($action == 'edit') {
        $id    = $_POST['id'];
        $nama  = trim($_POST['nama']);
        $email = trim($_POST['email']);
        $role  = $_POST['role'];

        // Cek email duplikat (kecuali milik sendiri)
        $cek = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $cek->execute([$email, $id]);

        if ($cek->fetch()) {
            $message = "Email sudah digunakan user lain!";
            $type    = "danger";
        } else {
            if (!empty($_POST['password'])) {
                // Update dengan password baru
                $hash = password_hash($_POST['password'], PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, password=?, role=? WHERE id=?");
                $stmt->execute([$nama, $email, $hash, $role, $id]);
            } else {
                // Update tanpa ubah password
                $stmt = $pdo->prepare("UPDATE users SET nama=?, email=?, role=? WHERE id=?");
                $stmt->execute([$nama, $email, $role, $id]);
            }
            $message = "User berhasil diupdate!";
            $type    = "success";
        }
    }

    // HAPUS USER
    if ($action == 'hapus') {
        $id = $_POST['id'];

        // Jangan hapus diri sendiri
        if ($id == $_SESSION['user_id']) {
            $message = "Tidak bisa menghapus akun sendiri!";
            $type    = "danger";
        } else {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
            $stmt->execute([$id]);
            $message = "User berhasil dihapus!";
            $type    = "success";
        }
    }
}

// Ambil semua user
$filter_role = $_GET['role'] ?? '';
$where       = '';
$params      = [];

if ($filter_role) {
    $where    = "WHERE role = ?";
    $params[] = $filter_role;
}

$users = $pdo->prepare("SELECT * FROM users $where ORDER BY role, nama");
$users->execute($params);
$users = $users->fetchAll();

// Data untuk edit (kalau ada)
$edit_user = null;
if (isset($_GET['edit'])) {
    $stmt      = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_GET['edit']]);
    $edit_user = $stmt->fetch();
}
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="d-flex justify-content-between align-items-center mb-4">
    <h4>👥 Manajemen User</h4>
    <a href="dashboard.php" class="btn btn-outline-secondary btn-sm">← Kembali</a>
</div>

<?php if ($message): ?>
<div class="alert alert-<?= $type ?> alert-dismissible fade show">
    <?= htmlspecialchars($message) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Form Tambah / Edit -->
    <div class="col-md-4">
        <div class="card shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0"><?= $edit_user ? '✏️ Edit User' : '➕ Tambah User' ?></h6>
            </div>
            <div class="card-body">
                <form method="POST">
                    <input type="hidden" name="action" value="<?= $edit_user ? 'edit' : 'tambah' ?>">
                    <?php if ($edit_user): ?>
                    <input type="hidden" name="id" value="<?= $edit_user['id'] ?>">
                    <?php endif; ?>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap</label>
                        <input type="text" name="nama" class="form-control"
                               value="<?= htmlspecialchars($edit_user['nama'] ?? '') ?>"
                               placeholder="Nama lengkap" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email</label>
                        <input type="email" name="email" class="form-control"
                               value="<?= htmlspecialchars($edit_user['email'] ?? '') ?>"
                               placeholder="email@contoh.com" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">
                            Password <?= $edit_user ? '<small class="text-muted">(kosongkan jika tidak diubah)</small>' : '' ?>
                        </label>
                        <input type="password" name="password" class="form-control"
                               placeholder="<?= $edit_user ? 'Kosongkan jika tidak diubah' : 'Minimal 6 karakter' ?>"
                               <?= $edit_user ? '' : 'required' ?>>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Role</label>
                        <select name="role" class="form-select" id="roleSelect" required>
                            <option value="siswa"  <?= ($edit_user['role'] ?? '') == 'siswa'  ? 'selected' : '' ?>>Siswa</option>
                            <option value="guru"   <?= ($edit_user['role'] ?? '') == 'guru'   ? 'selected' : '' ?>>Guru</option>
                            <option value="admin"  <?= ($edit_user['role'] ?? '') == 'admin'  ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </div>

                    <!-- Field nama kelas (hanya muncul kalau role = guru & tambah baru) -->
                    <?php if (!$edit_user): ?>
                    <div class="mb-3" id="kelasField" style="display:none">
                        <label class="form-label fw-bold">Nama Kelas <small class="text-muted">(opsional)</small></label>
                        <input type="text" name="nama_kelas" class="form-control"
                               placeholder="Contoh: Kelas X-A">
                    </div>
                    <?php endif; ?>

                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <?= $edit_user ? '💾 Simpan Perubahan' : '➕ Tambah User' ?>
                        </button>
                        <?php if ($edit_user): ?>
                        <a href="kelola_user.php" class="btn btn-outline-secondary">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Daftar User -->
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Daftar User</h6>
                <!-- Filter Role -->
                <div class="d-flex gap-1">
                    <a href="kelola_user.php" class="btn btn-sm <?= !$filter_role ? 'btn-primary' : 'btn-outline-primary' ?>">Semua</a>
                    <a href="?role=admin"  class="btn btn-sm <?= $filter_role == 'admin'  ? 'btn-danger'  : 'btn-outline-danger' ?>">Admin</a>
                    <a href="?role=guru"   class="btn btn-sm <?= $filter_role == 'guru'   ? 'btn-success' : 'btn-outline-success' ?>">Guru</a>
                    <a href="?role=siswa"  class="btn btn-sm <?= $filter_role == 'siswa'  ? 'btn-info'    : 'btn-outline-info' ?>">Siswa</a>
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-hover mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th>#</th>
                            <th>Nama</th>
                            <th>Email</th>
                            <th>Role</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                        <tr><td colspan="5" class="text-center py-4 text-muted">Tidak ada user</td></tr>
                        <?php endif; ?>
                        <?php foreach ($users as $i => $user):
                            $role_badge = match($user['role']) {
                                'admin' => 'danger',
                                'guru'  => 'success',
                                default => 'info'
                            };
                        ?>
                        <tr <?= $user['id'] == $_SESSION['user_id'] ? 'class="table-warning"' : '' ?>>
                            <td><?= $i + 1 ?></td>
                            <td>
                                <?= htmlspecialchars($user['nama']) ?>
                                <?php if ($user['id'] == $_SESSION['user_id']): ?>
                                <span class="badge bg-warning text-dark ms-1">Anda</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-muted small"><?= htmlspecialchars($user['email']) ?></td>
                            <td><span class="badge bg-<?= $role_badge ?>"><?= ucfirst($user['role']) ?></span></td>
                            <td>
                                <a href="?edit=<?= $user['id'] ?>"
                                   class="btn btn-sm btn-outline-primary">✏️</a>
                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                <form method="POST" class="d-inline"
                                      onsubmit="return confirm('Hapus user <?= htmlspecialchars($user['nama']) ?>?')">
                                    <input type="hidden" name="action" value="hapus">
                                    <input type="hidden" name="id" value="<?= $user['id'] ?>">
                                    <button class="btn btn-sm btn-outline-danger">🗑️</button>
                                </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Ringkasan -->
        <div class="row g-2 mt-2">
            <?php
            $count_admin = count(array_filter($users, fn($u) => $u['role'] == 'admin'));
            $count_guru  = count(array_filter($users, fn($u) => $u['role'] == 'guru'));
            $count_siswa = count(array_filter($users, fn($u) => $u['role'] == 'siswa'));
            // Kalau filter aktif, ambil total semua
            if ($filter_role) {
                $all = $pdo->query("SELECT role, COUNT(*) as total FROM users GROUP BY role")->fetchAll();
                $count_admin = $count_guru = $count_siswa = 0;
                foreach ($all as $r) {
                    if ($r['role'] == 'admin') $count_admin = $r['total'];
                    if ($r['role'] == 'guru')  $count_guru  = $r['total'];
                    if ($r['role'] == 'siswa') $count_siswa = $r['total'];
                }
            }
            ?>
            <div class="col-4">
                <div class="card border-0 bg-danger bg-opacity-10 text-center py-2">
                    <div class="fw-bold text-danger"><?= $count_admin ?></div>
                    <div class="small text-muted">Admin</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 bg-success bg-opacity-10 text-center py-2">
                    <div class="fw-bold text-success"><?= $count_guru ?></div>
                    <div class="small text-muted">Guru</div>
                </div>
            </div>
            <div class="col-4">
                <div class="card border-0 bg-info bg-opacity-10 text-center py-2">
                    <div class="fw-bold text-info"><?= $count_siswa ?></div>
                    <div class="small text-muted">Siswa</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Tampilkan field kelas kalau role = guru
document.getElementById('roleSelect').addEventListener('change', function() {
    const kelasField = document.getElementById('kelasField');
    if (kelasField) {
        kelasField.style.display = this.value === 'guru' ? 'block' : 'none';
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>