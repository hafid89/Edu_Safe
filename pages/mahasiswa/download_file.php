<?php
// pages/mahasiswa/download_file.php
require_once '../../includes/file_manager.php';
require_once '../../includes/auth_file.php';

// Cek login
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$error = '';
$all_files = FileManager::getAllFiles();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['download_file'])) {
    $file_id = $_POST['file_id'] ?? '';
    $encryption_key = trim($_POST['encryption_key'] ?? '');
    
    if (empty($file_id)) {
        $error = "Pilih file terlebih dahulu";
    } elseif (empty($encryption_key)) {
        $error = "Masukkan encryption key";
    } else {
        try {
            $download_info = FileManager::downloadDecryptedFile($file_id, $encryption_key);
            
            // Set headers untuk download
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . $download_info['file_name'] . '"');
            header('Content-Transfer-Encoding: binary');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . $download_info['file_size']);
            
            readfile($download_info['file_path']);
            unlink($download_info['file_path']); // Hapus file temporary
            exit;
            
        } catch (Exception $e) {
            $error = "Download gagal: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Download File Ujian - Mahasiswa</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .nav { margin: 20px 0; text-align: center; }
        .nav a { display: inline-block; margin: 0 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 5px; }
        .btn { background: #28a745; color: white; padding: 12px 30px; border: none; border-radius: 5px; cursor: pointer; width: 100%; font-size: 16px; }
        .btn:hover { background: #218838; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .file-list { margin-top: 30px; }
        .file-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .info-box { background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 5px; padding: 15px; margin: 20px 0; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔓 Download File Ujian - MAHASISWA</h1>
            <p>Download file ujian dengan encryption key dari dosen</p>
        </div>

        <div class="nav">
            <a href="../../index.php">← Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <form method="POST">
            <div class="form-group">
                <label>📁 Pilih File Ujian:</label>
                <select name="file_id" required>
                    <option value="">Pilih file ujian...</option>
                    <?php foreach ($all_files as $file): ?>
                        <option value="<?= $file['file_id'] ?>">
                            <?= htmlspecialchars($file['file_name']) ?> 
                            (<?= number_format($file['file_size']) ?> bytes)
                            - Upload: <?= date('d M Y', strtotime($file['created_at'])) ?>
                            - Downloads: <?= $file['download_count'] ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <?php if (empty($all_files)): ?>
                    <small style="color: #dc3545;">Tidak ada file ujian yang tersedia.</small>
                <?php endif; ?>
            </div>
            
            <div class="form-group">
                <label>🔑 Encryption Key dari Dosen:</label>
                <input type="text" name="encryption_key" placeholder="Masukkan 64 karakter encryption key dari dosen..." required maxlength="64" pattern="[a-fA-F0-9]{64}">
                <small>Minta encryption key kepada dosen yang mengupload file</small>
            </div>

            <div class="info-box">
                <h4>📋 Cara Download File Ujian:</h4>
                <ol style="margin-left: 20px;">
                    <li>Pilih file ujian yang ingin didownload</li>
                    <li>Dapatkan encryption key dari dosen pengampu</li>
                    <li>Masukkan encryption key yang diberikan (64 karakter hexadecimal)</li>
                    <li>Klik tombol "Download File Ujian"</li>
                    <li>File ujian akan terdownload secara otomatis</li>
                </ol>
            </div>

            <button type="submit" name="download_file" class="btn">
                ⬇️ Download File Ujian
            </button>
        </form>

        <?php if (!empty($all_files)): ?>
            <div class="file-list">
                <h3>📂 Semua File Ujian Tersedia</h3>
                <?php foreach ($all_files as $file): ?>
                    <div class="file-item">
                        <strong><?= htmlspecialchars($file['file_name']) ?></strong><br>
                        <small>
                            📏 Size: <?= number_format($file['file_size']) ?> bytes | 
                            👤 Uploader ID: <?= $file['user_id'] ?> | 
                            📥 Downloads: <?= $file['download_count'] ?> | 
                            📅 Upload: <?= date('d M Y H:i', strtotime($file['created_at'])) ?>
                            <?php if ($file['last_downloaded']): ?>
                                | ⏰ Last Download: <?= date('d M Y H:i', strtotime($file['last_downloaded'])) ?>
                            <?php endif; ?>
                        </small>
                        <?php if ($file['description']): ?>
                            <br><small>📝 <?= htmlspecialchars($file['description']) ?></small>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <script>
        // Auto-format key input
        document.querySelector('input[name="encryption_key"]').addEventListener('input', function(e) {
            // Hanya allow hexadecimal characters
            this.value = this.value.replace(/[^a-fA-F0-9]/g, '');
        });

        // Validasi sebelum submit
        document.querySelector('form').addEventListener('submit', function(e) {
            const keyInput = document.querySelector('input[name="encryption_key"]');
            if (keyInput.value.length !== 64) {
                alert('Encryption key harus tepat 64 karakter hexadecimal!');
                e.preventDefault();
                keyInput.focus();
            }
        });
    </script>
</body>
</html>