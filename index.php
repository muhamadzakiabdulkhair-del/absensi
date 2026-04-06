<?php
session_start();

// Kalau sudah login, redirect ke dashboard sesuai role
if (isset($_SESSION['user_id'])) {
    switch ($_SESSION['role']) {
        case 'admin': header('Location: admin/dashboard.php'); break;
        case 'guru':  header('Location: guru/dashboard.php');  break;
        case 'siswa': header('Location: siswa/dashboard.php'); break;
    }
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Absensi Online</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
    <style>
        body {
            background: linear-gradient(135deg, #1a73e8 0%, #0d47a1 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .hero-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
            max-width: 480px;
            width: 100%;
        }
        .hero-header {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            padding: 40px 30px;
            text-align: center;
            color: white;
        }
        .hero-icon {
            font-size: 64px;
            margin-bottom: 16px;
        }
        .feature-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .feature-item:last-child { border-bottom: none; }
        .feature-icon {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 18px;
            flex-shrink: 0;
        }
        .btn-login {
            background: linear-gradient(135deg, #1a73e8, #0d47a1);
            border: none;
            border-radius: 12px;
            padding: 14px;
            font-size: 16px;
            font-weight: 600;
            letter-spacing: 0.5px;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(26,115,232,0.4);
        }
        .role-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin: 4px;
        }
    </style>
</head>
<body>
<div class="container px-3">
    <div class="hero-card card mx-auto">

        <!-- Header -->
        <div class="hero-header">
            <div class="hero-icon">📋</div>
            <h2 class="fw-bold mb-1">Sistem Absensi Online</h2>
            <p class="mb-0 opacity-75">Platform absensi digital berbasis QR Code & Token</p>
        </div>

        <!-- Body -->
        <div class="card-body p-4">

            <!-- Fitur -->
            <h6 class="text-muted fw-bold mb-3 small text-uppercase">Fitur Unggulan</h6>
            <div class="mb-4">
                <div class="feature-item">
                    <div class="feature-icon bg-primary bg-opacity-10">📱</div>
                    <div>
                        <div class="fw-bold small">QR Code & Token</div>
                        <div class="text-muted" style="font-size:12px">Absensi cepat via scan QR atau input token</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon bg-success bg-opacity-10">⏱️</div>
                    <div>
                        <div class="fw-bold small">Berbasis Waktu</div>
                        <div class="text-muted" style="font-size:12px">Deteksi otomatis hadir & terlambat</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon bg-warning bg-opacity-10">📊</div>
                    <div>
                        <div class="fw-bold small">Rekap Kehadiran</div>
                        <div class="text-muted" style="font-size:12px">Laporan lengkap per siswa & kelas</div>
                    </div>
                </div>
                <div class="feature-item">
                    <div class="feature-icon bg-info bg-opacity-10">📥</div>
                    <div>
                        <div class="fw-bold small">Export Excel</div>
                        <div class="text-muted" style="font-size:12px">Download laporan format .xlsx</div>
                    </div>
                </div>
            </div>

            <!-- Role Info -->
            <h6 class="text-muted fw-bold mb-2 small text-uppercase">Login Sebagai</h6>
            <div class="mb-4 text-center">
                <span class="role-badge bg-danger bg-opacity-10 text-danger">👑 Admin</span>
                <span class="role-badge bg-success bg-opacity-10 text-success">👨‍🏫 Guru</span>
                <span class="role-badge bg-primary bg-opacity-10 text-primary">👨‍🎓 Siswa</span>
            </div>

            <!-- Tombol Login -->
            <div class="d-grid">
                <a href="login.php" class="btn btn-login btn-primary text-white">
                    Masuk Sekarang →
                </a>
            </div>

        </div>

        <!-- Footer -->
        <div class="card-footer bg-light text-center py-3">
            <small class="text-muted">© <?= date('Y') ?> Sistem Absensi Online</small>
        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>