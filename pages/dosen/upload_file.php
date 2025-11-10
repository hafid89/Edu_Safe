<?php
// pages/dosen/upload_file.php
require_once '../../includes/file_manager.php';
require_once '../../includes/auth_file.php';

// Cek login dan role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'dosen') {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';
$file_info = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload_file'])) {
    $description = trim($_POST['description'] ?? '');
    
    if (empty($description)) {
        $error = "Harap isi deskripsi file ujian";
    } elseif (!isset($_FILES['file_upload']) || $_FILES['file_upload']['error'] !== UPLOAD_ERR_OK) {
        $error = "Harap pilih file ujian untuk diupload";
    } else {
        try {
            $file_info = FileManager::uploadEncryptedFile(
                $_FILES['file_upload'],
                $user_id,
                $description
            );
            
            $success = "File ujian berhasil dienkripsi dan disimpan!";
            
        } catch (Exception $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload File Ujian - Dosen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .nav { margin: 20px 0; text-align: center; }
        .nav a { display: inline-block; margin: 0 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group textarea { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        .btn:hover { background: #218838; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .key-display { background: #e7f3ff; border: 2px solid #007bff; border-radius: 5px; padding: 20px; margin: 20px 0; }
        .file-info { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .info-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Upload File Ujian - DOSEN</h1>
            <p>Enkripsi file ujian untuk keamanan</p>
        </div>

        <div class="nav">
            <a href="../../index.php">← Dashboard</a>
            <a href="file_manager.php">📁 File Manager</a>
            <a href="../mahasiswa/download_file.php">⬇️ Download File</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <h3>✅ File Ujian Berhasil Dienkripsi!</h3>
                
                <?php if (!empty($file_info)): ?>
                    <div class="file-info">
                        <strong>📊 Info File:</strong><br>
                        - Nama File: <?= htmlspecialchars($file_info['file_name']) ?><br>
                        - Ukuran Asli: <?= number_format($file_info['original_size']) ?> bytes<br>
                        - Ukuran Terenkripsi: <?= number_format($file_info['encrypted_size']) ?> bytes<br>
                        - File ID: <?= htmlspecialchars($file_info['file_id']) ?>
                    </div>
                    
                    <div class="key-display">
                        <strong>🔑 ENCRYPTION KEY (Berikan kepada mahasiswa):</strong><br>
                        <code style="font-size: 14px; background: white; padding: 10px; border-radius: 3px; display: block; margin: 10px 0; word-break: break-all;">
                            <?= htmlspecialchars($file_info['encryption_key']) ?>
                        </code>
                        <small style="color: #dc3545;">
                            ⚠️ SIMPAN KEY INI! Key diperlukan untuk mendownload file ujian.
                        </small>
                    </div>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <div class="form-group">
                <label>📝 Deskripsi File Ujian:</label>
                <input type="text" name="description" placeholder="Contoh: Ujian Mid Semester Matematika Diskrit - 2024" value="<?= htmlspecialchars($_POST['description'] ?? '') ?>" required>
            </div>
            
            <div class="form-group">
                <label>📁 Pilih File Ujian:</label>
                <input type="file" name="file_upload" accept=".pdf,.doc,.docx,.zip,.rar" required>
                <small>Maksimal 100MB - Format: PDF, DOC, ZIP, dll.</small>
            </div>

            <div class="info-box">
                <h4>🔐 Sistem Keamanan File Ujian</h4>
                <p><strong>File ujian Anda dilindungi dengan:</strong></p>
                <ul style="margin-left: 20px; line-height: 1.6;">
                    <li><strong>AES-256-GCM</strong> - Algoritma enkripsi tingkat militer dengan autentikasi bawaan (authenticated encryption), memastikan file tetap rahasia dan tidak dapat dimodifikasi tanpa izin.</li>
                    <li><strong>HMAC-SHA512</strong> - Lapisan verifikasi tambahan untuk menjamin integritas dan keaslian file dengan kode autentikasi 512-bit.</li>
                    <li><strong>Key 256-bit</strong> - Setiap file dienkripsi menggunakan kunci acak 256-bit (32 byte) yang berbeda, menjamin keamanan individual setiap file.</li>
                    <li><strong>Kontrol Akses ketat</strong> - Hanya mahasiswa dengan encryption key yang bisa download</li>
                </ul>
            </div>

            <button type="submit" name="upload_file" class="btn">
                🔒 Upload & Enkripsi File Ujian
            </button>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function(e) {
            const fileInput = document.querySelector('input[type="file"]');
            const file = fileInput.files[0];
            
            if (!file) {
                e.preventDefault();
                alert('Harap pilih file ujian terlebih dahulu.');
                return false;
            }
            
            if (file.size > 100 * 1024 * 1024) {
                e.preventDefault();
                alert('Ukuran file terlalu besar. Maksimal 100MB.');
                return false;
            }
            
            return true;
        });
    </script>
</body>
</html>