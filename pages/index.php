<?php
// pages/index.php
require_once '../includes/auth.php';
Auth::requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

// Get stats
$pdo = DatabaseConfig::getConnection();

// Hitung pesan
$message_count = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE sender_id = ? OR receiver_id = ?");
$message_count->execute([$user_id, $user_id]);
$message_count = $message_count->fetchColumn();

// Hitung file terenkripsi
$file_count = $pdo->prepare("SELECT COUNT(*) FROM encrypted_files WHERE user_id = ?");
$file_count->execute([$user_id]);
$file_count = $file_count->fetchColumn();

// Hitung sertifikat
$certificate_count = $pdo->prepare("SELECT COUNT(*) FROM certificates WHERE created_by = ?");
$certificate_count->execute([$user_id]);
$certificate_count = $certificate_count->fetchColumn();

// Tentukan role group
$is_encryptor = in_array($role, ['dosen', 'penguji']);
$is_decryptor = in_array($role, ['mahasiswa']);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - EduSafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f8f9fa;
            color: #333;
            line-height: 1.6;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 25px 0;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .header-content {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 20px;
        }
        .user-info {
            text-align: right;
        }
        .user-info p {
            margin-bottom: 5px;
        }
        .nav {
            background: white;
            padding: 15px 0;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            border-bottom: 1px solid #e9ecef;
        }
        .nav-content {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        .nav ul {
            list-style: none;
            display: flex;
            gap: 15px;
            flex-wrap: wrap;
        }
        .nav a {
            text-decoration: none;
            color: #495057;
            padding: 10px 20px;
            border-radius: 25px;
            transition: all 0.3s ease;
            font-weight: 500;
            border: 2px solid transparent;
        }
        .nav a:hover {
            background: #667eea;
            color: white;
            border-color: #667eea;
        }
        .container {
            max-width: 1200px;
            margin: 30px auto;
            padding: 0 20px;
        }
        .welcome-section {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
            text-align: center;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            text-align: center;
            transition: transform 0.3s ease;
            border-left: 5px solid #667eea;
        }
        .stat-card:hover {
            transform: translateY(-5px);
        }
        .stat-number {
            font-size: 42px;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }
        .stat-label {
            color: #6c757d;
            font-size: 16px;
            font-weight: 500;
        }
        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(350px, 1fr));
            gap: 25px;
        }
        .feature-card {
            background: white;
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 8px 25px rgba(0,0,0,0.1);
            transition: all 0.3s ease;
            border-top: 4px solid #667eea;
        }
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        .feature-card h3 {
            color: #495057;
            margin-bottom: 15px;
            font-size: 22px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .feature-card p {
            color: #6c757d;
            margin-bottom: 20px;
            line-height: 1.6;
        }
        .btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.3s ease;
            border: none;
            cursor: pointer;
            text-align: center;
        }
        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.3);
        }
        .btn-success {
            background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        }
        .btn-success:hover {
            box-shadow: 0 8px 20px rgba(40, 167, 69, 0.3);
        }
        .btn-warning {
            background: linear-gradient(135deg, #ffc107 0%, #fd7e14 100%);
        }
        .btn-warning:hover {
            box-shadow: 0 8px 20px rgba(255, 193, 7, 0.3);
        }
        .logout-btn {
            background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        }
        .logout-btn:hover {
            box-shadow: 0 8px 20px rgba(220, 53, 69, 0.3);
        }
        .role-badge {
            display: inline-block;
            background: rgba(255,255,255,0.2);
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .feature-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .encryptor-section, .decryptor-section {
            margin-bottom: 40px;
        }
        .section-title {
            color: #495057;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 3px solid #667eea;
            display: inline-block;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-content">
            <h1 style="font-size: 2.5rem; font-weight: 700;">🏫 EduSafe Dashboard</h1>
            <div class="user-info">
                <p style="font-size: 1.2rem;">Selamat datang, <strong><?= htmlspecialchars($username) ?></strong></p>
                <p>Role: <span class="role-badge"><?= htmlspecialchars($role) ?></span></p>
                <a href="logout.php" class="btn logout-btn" style="margin-top: 15px; padding: 10px 20px; font-size: 14px;">🚪 Logout</a>
            </div>
        </div>
    </div>

    <div class="nav">
        <div class="nav-content">
            <ul>
                <li><a href="dashboard.php">📊 Dashboard</a></li>
                <li><a href="messaging.php">💬 Pesan Aman</a></li>
                
                <?php if ($is_encryptor): ?>
                    <li><a href="dosen/upload_file.php">🔒 Enkripsi File</a></li>
                    <li><a href="certificate_generator.php">🖼️ Steganografi</a></li>
                    <li><a href="dosen/file_manager.php">📁 File Manager</a></li>
                <?php endif; ?>
                
                <?php if ($is_decryptor): ?>
                    <li><a href="mahasiswa/download_file.php">📥 Download File</a></li>
                    <li><a href="certificate_viewer.php">👁️ Lihat Sertifikat</a></li>
                <?php endif; ?>
            </ul>
        </div>
    </div>

    <div class="container">
        <!-- Welcome Section -->
        <div class="welcome-section">
            <h2 style="font-size: 2rem; margin-bottom: 15px;">Selamat Datang di Sistem EduSafe</h2>
            <p style="font-size: 1.1rem; max-width: 800px; margin: 0 auto;">
                Platform keamanan akademik terintegrasi untuk melindungi data dan komunikasi sensitif 
                dengan teknologi enkripsi mutakhir.
            </p>
        </div>

        <!-- Statistics -->
        <div class="stats">
            <div class="stat-card">
                <div class="stat-number"><?= $message_count ?></div>
                <div class="stat-label">Total Pesan</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $file_count ?></div>
                <div class="stat-label">File Terenkripsi</div>
            </div>
            <div class="stat-card">
                <div class="stat-number"><?= $certificate_count ?></div>
                <div class="stat-label">Sertifikat</div>
            </div>
        </div>

        <!-- Features for Encryptors (Dosen & Penguji) -->
        <?php if ($is_encryptor): ?>
        <div class="encryptor-section">
            <h2 class="section-title">🛡️ Fitur Enkripsi & Keamanan</h2>
            <div class="features">
                <div class="feature-card">
                    <h3>🔐 Enkripsi File</h3>
                    <p>Upload dan enkripsi file penting seperti soal ujian, materi kuliah, atau dokumen sensitif menggunakan algoritma ChaCha20-Poly1305 + HMAC-SHA512.</p>
                    <div class="feature-buttons">
                        <a href="dosen/upload_file.php" class="btn">⬆️ Upload File</a>
                        <a href="dosen/file_manager.php" class="btn btn-success">📁 Kelola File</a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <h3>🖼️ Steganografi</h3>
                    <p>Sembunyikan informasi rahasia dalam gambar menggunakan teknik LSB steganography. Cocok untuk membuat sertifikat aman dan dokumen rahasia.</p>
                    <div class="feature-buttons">
                        <a href="certificate_generator.php" class="btn">🎨 Buat Sertifikat</a>
                        <a href="certificate_viewer.php" class="btn btn-success">👁️ Lihat Sertifikat</a>
                    </div>
                </div>
                
            </div>
        </div>
        <?php endif; ?>

        <!-- Features for Decryptors (Mahasiswa) -->
        <?php if ($is_decryptor): ?>
        <div class="decryptor-section">
            <h2 class="section-title">📥 Fitur Download & Dekripsi</h2>
            <div class="features">
                <div class="feature-card">
                    <h3>📥 Download File</h3>
                    <p>Download dan dekripsi file yang dibagikan oleh dosen atau penguji. Masukkan kunci enkripsi yang diberikan untuk mengakses file.</p>
                    <div class="feature-buttons">
                        <a href="mahasiswa/download_file.php" class="btn">⬇️ Download File</a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <h3>👁️ Lihat Sertifikat</h3>
                    <p>Ekstrak pesan rahasia dari sertifikat digital. Masukkan kunci dan nama pembuat untuk melihat informasi tersembunyi.</p>
                    <div class="feature-buttons">
                        <a href="certificate_viewer.php" class="btn">🔍 Ekstrak Pesan</a>
                    </div>
                </div>
                
                <div class="feature-card">
                    <h3>📋 Riwayat File</h3>
                    <p>Lihat semua file yang telah Anda download dan riwayat akses file terenkripsi.</p>
                    <div class="feature-buttons">
                        <a href="file_manager.php" class="btn btn-success">📊 Lihat Riwayat</a>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Common Features for All Roles -->
        <div class="features">
            <div class="feature-card">
                <h3>💬 Pesan Aman</h3>
                <p>Kirim dan terima pesan terenkripsi dengan pengguna lain. Semua pesan dilindungi dengan super enkripsi (Affine + Vigenere + AES-256).</p>
                <div class="feature-buttons">
                    <a href="messaging.php" class="btn">✉️ Buka Pesan</a>
                </div>
            </div>
            
            <div class="feature-card">
                <h3>ℹ️ Panduan Penggunaan</h3>
                <p>Pelajari cara menggunakan semua fitur EduSafe dengan panduan lengkap dan tips keamanan.</p>
                <div class="feature-buttons">
                    <a href="#" class="btn btn-success">📚 Baca Panduan</a>
                    <a href="#" class="btn btn-warning">❓ Bantuan</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Animasi untuk stat cards
        document.addEventListener('DOMContentLoaded', function() {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '1';
                    card.style.transform = 'translateY(0)';
                }, index * 200);
            });
        });
    </script>
</body>
</html>