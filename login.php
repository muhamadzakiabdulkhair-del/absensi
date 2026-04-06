<?php
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE);
ini_set('display_errors', 1);
session_start();
require __DIR__ . '/config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Cek captcha dulu
    $captcha_input = trim($_POST['captcha'] ?? '');

    if (strtolower($captcha_input) !== strtolower($_SESSION['captcha'] ?? '')) {
        $error = "Kode captcha salah! Silakan coba lagi.";
        // Reset captcha
        unset($_SESSION['captcha']);
    } else {
        $email    = trim($_POST['email']);
        $password = trim($_POST['password']);

        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role']    = $user['role'];
            $_SESSION['nama']    = $user['nama'];
            unset($_SESSION['captcha']);

            if ($user['role'] == 'admin')       header('Location: admin/dashboard.php');
            elseif ($user['role'] == 'guru')    header('Location: guru/dashboard.php');
            else                                header('Location: siswa/dashboard.php');
            exit;
        } else {
            $error = "Email atau password salah!";
            unset($_SESSION['captcha']);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Sistem Absensi</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body { background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%); min-height: 100vh; display: flex; align-items: center; }
        .card { border: none; border-radius: 16px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); }
        .card-header { border-radius: 16px 16px 0 0 !important; background: linear-gradient(135deg, #1a73e8, #0d47a1) !important; }
        .btn-primary { background: linear-gradient(135deg, #1a73e8, #0d47a1); border: none; border-radius: 10px; }
        .form-control { border-radius: 10px; }
        .captcha-img { 
            border-radius: 8px; 
            border: 2px solid #dee2e6; 
            cursor: pointer;
            height: 50px;
        }
        .captcha-img:hover { border-color: #1a73e8; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-4 col-sm-8">

            <div class="text-center mb-4">
                <span style="font-size:48px">📋</span>
                <h4 class="text-white mt-2">Sistem Absensi Online</h4>
            </div>

            <div class="card">
                <div class="card-header text-white text-center py-3">
                    <h5 class="mb-0">Login</h5>
                </div>
                <div class="card-body p-4">

                    <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show py-2">
                        <?= htmlspecialchars($error) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <div class="mb-3">
                            <label class="form-label fw-bold">Email</label>
                            <input type="email"
                                   name="email"
                                   class="form-control"
                                   placeholder="Masukkan email"
                                   value="<?= isset($_POST['email']) ? htmlspecialchars($_POST['email']) : '' ?>"
                                   required autofocus>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Password</label>
                            <div class="input-group">
                                <input type="password"
                                       name="password"
                                       id="passwordInput"
                                       class="form-control"
                                       placeholder="Masukkan password"
                                       required>
                                <button class="btn btn-outline-secondary" type="button"
                                        onclick="togglePassword()">👁️</button>
                            </div>
                        </div>

                        <!-- CAPTCHA -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Kode Keamanan</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <img src="includes/captcha.php?<?= time() ?>"
                                     id="captchaImg"
                                     class="captcha-img"
                                     alt="Captcha"
                                     title="Klik untuk refresh"
                                     onclick="refreshCaptcha()"
                                     width="160" height="50">
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        onclick="refreshCaptcha()"
                                        title="Refresh captcha">
                                    🔄
                                </button>
                            </div>
                            <input type="text"
                                   name="captcha"
                                   class="form-control text-center fw-bold"
                                   style="letter-spacing:4px; font-size:18px"
                                   placeholder="Masukkan kode di atas"
                                   maxlength="6"
                                   autocomplete="off"
                                   required>
                            <div class="form-text">
                                Klik gambar atau 🔄 untuk refresh kode
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary w-100 py-2 fw-bold">
                            Masuk →
                        </button>
                    </form>

                </div>
                <div class="card-footer text-center bg-light py-2">
                    <small class="text-muted">© <?= date('Y') ?> Sistem Absensi Online</small>
                </div>
            </div>

            <div class="text-center mt-3">
                <a href="index.php" class="text-white text-decoration-none small">← Kembali ke Beranda</a>
            </div>

        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
function refreshCaptcha() {
    document.getElementById('captchaImg').src = 'includes/captcha.php?' + Date.now();
    document.querySelector('[name=captcha]').value = '';
    document.querySelector('[name=captcha]').focus();
}

function togglePassword() {
    const input = document.getElementById('passwordInput');
    input.type = input.type === 'password' ? 'text' : 'password';
}

// Auto refresh captcha kalau salah (ada alert error)
<?php if ($error): ?>
setTimeout(() => refreshCaptcha(), 100);
<?php endif; ?>
</script>
</body>
</html>