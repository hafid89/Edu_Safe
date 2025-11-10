<?php
// pages/certificate_generator.php
require_once '../includes/auth.php';
require_once '../includes/databaseSG.php';
require_once '../includes/steganography.php';
Auth::requireLogin();

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$error = '';
$success = '';
$generated_key = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_certificate'])) {
    $student_name = trim($_POST['student_name'] ?? '');
    $course_name = trim($_POST['course_name'] ?? '');
    $message = trim($_POST['hidden_message'] ?? '');
    
    if (empty($student_name) || empty($course_name) || empty($message)) {
        $error = "Harap isi semua field";
    } elseif (!isset($_FILES['certificate_image']) || $_FILES['certificate_image']['error'] !== UPLOAD_ERR_OK) {
        $error = "Harap pilih gambar sertifikat";
    } else {
        $image_file = $_FILES['certificate_image'];
        
        // Validasi file
        $file_extension = strtolower(pathinfo($image_file['name'], PATHINFO_EXTENSION));
        if (!in_array($file_extension, ['jpg', 'jpeg', 'png'])) {
            $error = "Format file tidak didukung. Hanya JPG, JPEG, dan PNG yang diperbolehkan.";
        } elseif ($image_file['size'] > 10 * 1024 * 1024) {
            $error = "Ukuran file terlalu besar. Maksimal 10MB.";
        } else {
            try {
                // Buat folder uploads jika belum ada
                $upload_dir = '../uploads/certificates/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }
                
                // Generate unique key (32 karakter hex)
                $generated_key = bin2hex(random_bytes(16));
                $file_id = uniqid('cert_', true);
                
                // Simpan gambar original
                $original_filename = $file_id . '_original.' . $file_extension;
                $original_path = $upload_dir . $original_filename;
                
                if (!move_uploaded_file($image_file['tmp_name'], $original_path)) {
                    throw new Exception("Gagal mengupload gambar original");
                }
                
                // Generate stego image
                $stego_filename = $file_id . '_stego.' . $file_extension;
                $stego_path = $upload_dir . $stego_filename;
                
                // Sembunyikan pesan dengan LSB
                Steganography::hideMessage($original_path, $stego_path, $message, $generated_key);
                
                // Verifikasi file dibuat
                if (!file_exists($stego_path)) {
                    throw new Exception("Gambar stego tidak berhasil dibuat");
                }
                
                // Test extraction untuk validasi
                try {
                    $test_extracted = Steganography::extractMessage($stego_path, $generated_key);
                    if ($test_extracted !== $message) {
                        error_log("Validasi gagal: Diekstrak: '$test_extracted', Diharapkan: '$message'");
                    }
                } catch (Exception $e) {
                    error_log("Validasi extraction gagal: " . $e->getMessage());
                }
                
                // Simpan ke database
                if (DatabaseHelper::saveCertificateWithUniqueKey(
                    $student_name, 
                    $course_name, 
                    $original_filename, 
                    $stego_filename, 
                    $user_id,
                    $username,
                    $generated_key
                )) {
                    $success = "Sertifikat berhasil dibuat!";
                } else {
                    throw new Exception("Gagal menyimpan ke database");
                }
                
            } catch (Exception $e) {
                $error = "Error: " . $e->getMessage();
                // Hapus file jika gagal
                if (isset($original_path) && file_exists($original_path)) unlink($original_path);
                if (isset($stego_path) && file_exists($stego_path)) unlink($stego_path);
            }
        }
    }
}

$user_certificates = DatabaseHelper::getCertificatesByCreator($user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Generator - EduSafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #667eea; color: white; padding: 20px; border-radius: 10px 10px 0 0; text-align: center; }
        .nav { margin-top: 15px; }
        .nav a { color: white; text-decoration: none; margin: 0 10px; padding: 8px 16px; border: 2px solid rgba(255,255,255,0.3); border-radius: 25px; }
        .nav a:hover { background: rgba(255,255,255,0.2); }
        .content { display: grid; grid-template-columns: 1fr 1fr; gap: 0; }
        .generator-section { background: #f8f9fa; padding: 30px; border-right: 1px solid #e9ecef; }
        .certificates-section { background: white; padding: 30px; max-height: 600px; overflow-y: auto; }
        .form-group { margin-bottom: 20px; }
        .form-group label { display: block; margin-bottom: 8px; font-weight: bold; }
        .form-group input, .form-group textarea, .form-group select { width: 100%; padding: 12px; border: 2px solid #ddd; border-radius: 5px; }
        .btn { background: #667eea; color: white; padding: 12px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        .btn:hover { background: #764ba2; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 15px; }
        .key-display { background: #e7f3ff; padding: 15px; border-radius: 5px; margin: 10px 0; }
        .certificate-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🖼️ Certificate Generator</h1>
            <div class="nav">
                <a href="index.php">← Dashboard</a>
                <a href="certificate_keys.php">🔑 My Keys</a>
                <a href="certificate_viewer.php">👁️ View Certificates</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success">
                <h3>✅ Sertifikat Berhasil Dibuat!</h3>
                <div class="key-display">
                    <strong>🔑 ENCRYPTION KEY:</strong><br>
                    <code style="font-size: 16px; background: white; padding: 10px; display: block; margin: 10px 0;">
                        <?= htmlspecialchars($generated_key) ?>
                    </code>
                    <small>Simpan key ini! Dibutuhkan untuk mengekstrak pesan rahasia.</small>
                </div>
            </div>
        <?php endif; ?>

        <div class="content">
            <div class="generator-section">
                <h3>🔐 Buat Sertifikat Aman</h3>
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>👤 Nama Siswa:</label>
                        <input type="text" name="student_name" value="<?= htmlspecialchars($_POST['student_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>📚 Mata Pelajaran:</label>
                        <input type="text" name="course_name" value="<?= htmlspecialchars($_POST['course_name'] ?? '') ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>🖼️ Template Sertifikat:</label>
                        <input type="file" name="certificate_image" accept=".jpg,.jpeg,.png" required>
                        <small>Format: JPEG, PNG | Maks: 10MB</small>
                    </div>
                    
                    <div class="form-group">
                        <label>🕵️ Pesan Rahasia:</label>
                        <textarea name="hidden_message" placeholder="Masukkan pesan rahasia (nilai, komentar, kode verifikasi)" required><?= htmlspecialchars($_POST['hidden_message'] ?? '') ?></textarea>
                    </div>

                    <button type="submit" name="generate_certificate" class="btn">
                        🎨 Generate Secure Certificate
                    </button>
                </form>
            </div>

            <div class="certificates-section">
                <h3>📂 Sertifikat Anda</h3>
                <?php if (empty($user_certificates)): ?>
                    <p>Belum ada sertifikat.</p>
                <?php else: ?>
                    <?php foreach ($user_certificates as $cert): ?>
                        <div class="certificate-item">
                            <strong>🎓 <?= htmlspecialchars($cert['student_name']) ?> - <?= htmlspecialchars($cert['course_name']) ?></strong><br>
                            <small>📅 <?= date('d M Y', strtotime($cert['created_at'])) ?> | 👤 <?= htmlspecialchars($cert['creator_name']) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>