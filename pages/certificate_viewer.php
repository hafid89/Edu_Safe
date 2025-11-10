<?php
// pages/certificate_viewer.php
require_once '../includes/auth.php';
require_once '../includes/databaseSG.php';
require_once '../includes/steganography.php';
Auth::requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$extracted_message = '';
$success = '';

// Get all certificates
try {
    $certificates = DatabaseHelper::getAllCertificates();
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
    $certificates = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['extract_message'])) {
    $certificate_id = $_POST['certificate_id'] ?? '';
    $creator_name = trim($_POST['creator_name'] ?? '');
    $encryption_key = trim($_POST['encryption_key'] ?? '');
    
    if (empty($certificate_id)) {
        $error = "Pilih sertifikat terlebih dahulu";
    } elseif (empty($creator_name)) {
        $error = "Masukkan nama pembuat sertifikat";
    } elseif (empty($encryption_key)) {
        $error = "Masukkan encryption key";
    } else {
        try {
            $certificate = DatabaseHelper::getCertificateById($certificate_id);
            
            if (!$certificate) {
                $error = "Sertifikat tidak ditemukan";
            } elseif (strtolower($certificate['creator_name']) !== strtolower($creator_name)) {
                $error = "Nama pembuat tidak sesuai";
            } else {
                $stego_image_path = '../uploads/certificates/' . $certificate['stego_image_path'];
                
                if (!file_exists($stego_image_path)) {
                    $error = "File gambar tidak ditemukan: " . $stego_image_path;
                } else {
                    // Ekstrak pesan rahasia
                    $extracted_message = Steganography::extractMessage($stego_image_path, $encryption_key);
                    $success = "Pesan berhasil diekstrak!";
                }
            }
        } catch (Exception $e) {
            $error = "Ekstraksi gagal: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificate Viewer - EduSafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #667eea; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .content { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
        .extract-section, .certificates-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; width: 100%; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .extracted-message { background: #e7f3ff; padding: 15px; border-radius: 5px; margin-top: 15px; font-size: 18px; font-weight: bold; }
        .certificate-item { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 Certificate Viewer</h1>
            <a href="index.php" style="color: white;">← Back to Dashboard</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="content">
            <div class="extract-section">
                <h3>🔓 Ekstrak Pesan Rahasia</h3>
                <form method="POST">
                    <div class="form-group">
                        <label>Pilih Sertifikat:</label>
                        <select name="certificate_id" required>
                            <option value="">Pilih sertifikat...</option>
                            <?php foreach ($certificates as $cert): ?>
                                <option value="<?= $cert['id'] ?>">
                                    <?= htmlspecialchars($cert['student_name']) ?> - <?= htmlspecialchars($cert['course_name']) ?>
                                    (by <?= htmlspecialchars($cert['creator_name']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Nama Pembuat:</label>
                        <input type="text" name="creator_name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Encryption Key:</label>
                        <input type="text" name="encryption_key" required>
                        <small>Dapatkan dari pembuat sertifikat</small>
                    </div>
                    
                    <button type="submit" name="extract_message" class="btn">
                        🔍 Extract Hidden Message
                    </button>
                </form>

                <?php if ($extracted_message): ?>
                    <div class="extracted-message">
                        <strong>📜 PESAN RAHASIA:</strong><br>
                        <?= nl2br(htmlspecialchars($extracted_message)) ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="certificates-section">
                <h3>Available Certificates</h3>
                <?php if (empty($certificates)): ?>
                    <p>Tidak ada sertifikat.</p>
                <?php else: ?>
                    <?php foreach ($certificates as $cert): ?>
                        <div class="certificate-item">
                            <strong><?= htmlspecialchars($cert['student_name']) ?> - <?= htmlspecialchars($cert['course_name']) ?></strong><br>
                            <small>By: <?= htmlspecialchars($cert['creator_name']) ?> | Date: <?= date('M j, Y', strtotime($cert['created_at'])) ?></small>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>