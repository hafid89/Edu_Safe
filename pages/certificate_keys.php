<?php
// pages/certificate_keys.php
require_once '../includes/auth.php';
require_once '../includes/databaseSG.php';
Auth::requireLogin();

$user_id = $_SESSION['user_id'];
$user_certificates = DatabaseHelper::getCertificatesByCreator($user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Certificate Keys - EduSafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .header { background: #667eea; color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; text-align: center; }
        .key-item { border: 1px solid #ddd; padding: 20px; margin-bottom: 15px; border-radius: 10px; }
        .key-value { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 10px 0; font-family: monospace; }
        .btn { background: #667eea; color: white; padding: 8px 15px; border: none; border-radius: 5px; cursor: pointer; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔑 My Certificate Keys</h1>
            <a href="dashboard.php" style="color: white;">← Back to Dashboard</a>
        </div>

        <div style="background: #fff3cd; padding: 15px; border-radius: 5px; margin-bottom: 20px;">
            <strong>⚠️ Penting:</strong> Simpan keys dengan aman. Keys ini diperlukan untuk mengekstrak pesan rahasia dari sertifikat dan tidak dapat dipulihkan jika hilang.
        </div>

        <?php if (empty($user_certificates)): ?>
            <div style="text-align: center; padding: 40px;">
                <h3>📭 Belum ada sertifikat</h3>
                <p>Buat sertifikat pertama Anda di halaman Certificate Generator</p>
                <a href="certificate_generator.php" class="btn">Buat Sertifikat</a>
            </div>
        <?php else: ?>
            <?php foreach ($user_certificates as $cert): ?>
                <div class="key-item">
                    <h3>🎓 <?= htmlspecialchars($cert['student_name']) ?> - <?= htmlspecialchars($cert['course_name']) ?></h3>
                    <p><strong>📅 Dibuat:</strong> <?= date('d M Y H:i', strtotime($cert['created_at'])) ?></p>
                    <p><strong>🔑 Encryption Key:</strong></p>
                    <div class="key-value" id="key-<?= $cert['id'] ?>">
                        <?= htmlspecialchars($cert['encryption_key']) ?>
                    </div>
                    <button onclick="copyKey(<?= $cert['id'] ?>)" class="btn">📋 Copy Key</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script>
        function copyKey(certId) {
            const keyElement = document.getElementById('key-' + certId);
            const keyText = keyElement.textContent;
            
            navigator.clipboard.writeText(keyText).then(() => {
                alert('Key berhasil disalin!');
            }).catch(err => {
                alert('Gagal menyalin key: ' + err);
            });
        }
    </script>
</body>
</html>