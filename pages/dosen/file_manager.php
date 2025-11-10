<?php
// pages/dosen/file_manager.php
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

// Handle file deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_file'])) {
    $file_id = $_POST['file_id'] ?? '';
    
    if (empty($file_id)) {
        $error = "File ID tidak valid";
    } else {
        try {
            if (FileManager::deleteFile($file_id, $user_id)) {
                $success = "File berhasil dihapus";
            } else {
                throw new Exception("Gagal menghapus file");
            }
        } catch (Exception $e) {
            $error = "Error menghapus file: " . $e->getMessage();
        }
    }
}

$user_files = FileManager::getUserFiles($user_id);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>File Manager - Dosen</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 1000px; margin: 0 auto; background: white; border-radius: 10px; padding: 30px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        .header { text-align: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #eee; }
        .nav { margin: 20px 0; text-align: center; }
        .nav a { display: inline-block; margin: 0 10px; padding: 10px 20px; background: #007bff; color: white; text-decoration: none; border-radius: 5px; }
        .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 30px; }
        .stat-card { background: #f8f9fa; padding: 20px; border-radius: 5px; text-align: center; border-left: 4px solid #007bff; }
        .file-table { width: 100%; border-collapse: collapse; }
        .file-table th, .file-table td { padding: 12px; text-align: left; border-bottom: 1px solid #ddd; }
        .file-table th { background: #f8f9fa; }
        .btn { padding: 8px 15px; border: none; border-radius: 3px; cursor: pointer; text-decoration: none; display: inline-block; font-size: 14px; }
        .btn-delete { background: #dc3545; color: white; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .success { background: #d4edda; color: #155724; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .empty-state { text-align: center; padding: 40px; color: #6c757d; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>📁 File Manager - DOSEN</h1>
            <p>Kelola file ujian yang Anda upload</p>
        </div>

        <div class="nav">
            <a href="../../index.php">← Dashboard</a>
            <a href="upload_file.php">⬆️ Upload File</a>
            <a href="../mahasiswa/download_file.php">⬇️ Download File</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <!-- Statistics -->
        <?php if (!empty($user_files)): ?>
            <div class="stats">
                <div class="stat-card">
                    <div style="font-size: 24px; font-weight: bold;"><?= count($user_files) ?></div>
                    <div>Total File</div>
                </div>
                <div class="stat-card">
                    <div style="font-size: 24px; font-weight: bold;"><?= array_sum(array_column($user_files, 'download_count')) ?></div>
                    <div>Total Downloads</div>
                </div>
            </div>
        <?php endif; ?>

        <!-- File Table -->
        <?php if (empty($user_files)): ?>
            <div class="empty-state">
                <h2>📭 Belum ada file ujian</h2>
                <p>Upload file ujian pertama Anda untuk mulai menggunakan sistem</p>
                <a href="upload_file.php" class="btn" style="background: #28a745; color: white; padding: 12px 24px; margin-top: 15px;">
                    ⬆️ Upload File Ujian Pertama
                </a>
            </div>
        <?php else: ?>
            <table class="file-table">
                <thead>
                    <tr>
                        <th>Nama File</th>
                        <th>Deskripsi</th>
                        <th>Ukuran</th>
                        <th>Downloads</th>
                        <th>Upload Date</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($user_files as $file): ?>
                        <tr>
                            <td>
                                <strong><?= htmlspecialchars($file['file_name']) ?></strong><br>
                                <small style="color: #6c757d;">ID: <?= $file['file_id'] ?></small>
                            </td>
                            <td><?= htmlspecialchars($file['description'] ?? '-') ?></td>
                            <td><?= number_format($file['file_size']) ?> bytes</td>
                            <td><?= $file['download_count'] ?></td>
                            <td><?= date('d M Y H:i', strtotime($file['created_at'])) ?></td>
                            <td>
                                <form method="POST" style="display: inline;" onsubmit="return confirm('Hapus file <?= htmlspecialchars($file['file_name']) ?>?')">
                                    <input type="hidden" name="file_id" value="<?= $file['file_id'] ?>">
                                    <button type="submit" name="delete_file" class="btn btn-delete">🗑️ Hapus</button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</body>
</html>