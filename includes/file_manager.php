<?php
//includes/file_manager.php
require_once '../../config/database_file.php';
require_once '../../includes/file_encryption_engine.php';
require_once '../../config/file_encryption_config.php';

class FileManager {
    
    /**
     * Upload dan enkripsi file
     */
      /**
     * Upload dan enkripsi file
     */
    public static function uploadEncryptedFile($fileData, $userId, $description = '') {
        $db = DatabaseConfig::getConnection();
        
        // Validasi file
        if ($fileData['size'] > FILE_MAX_SIZE) {
            throw new Exception("Ukuran file terlalu besar. Maksimal " . (FILE_MAX_SIZE / 1024 / 1024) . "MB.");
        }
        
        if ($fileData['size'] == 0) {
            throw new Exception("File tidak boleh kosong");
        }
        
        // Generate unique file ID dan encryption key
        $fileId = uniqid('ujian_', true);
        $encryptionKey = FileEncryptionEngine::generateKey();
        $encryptionKeyHex = bin2hex($encryptionKey);
        
        // DEBUG: Log key generation
        error_log("Generated Key Hex: $encryptionKeyHex");
        error_log("Generated Key Length: " . strlen($encryptionKeyHex));
        
        // Buat folder uploads jika belum ada
        if (!is_dir(FILE_UPLOAD_DIR)) {
            mkdir(FILE_UPLOAD_DIR, 0755, true);
        }
        
        // Path file
        $originalFilename = $fileData['name'];
        $encryptedPath = FILE_UPLOAD_DIR . $fileId . '.enc';
        
        // Pindahkan file uploaded temporary
        $tempPath = FILE_UPLOAD_DIR . $fileId . '_temp';
        if (!move_uploaded_file($fileData['tmp_name'], $tempPath)) {
            throw new Exception("Gagal mengupload file");
        }
        
        try {
            // Enkripsi file dengan key BINARY
            $encryptInfo = FileEncryptionEngine::encryptFile($tempPath, $encryptedPath, $encryptionKey);
            
            // Hapus file temporary
            unlink($tempPath);
            
            // Simpan ke database - pastikan key hex disimpan dengan benar
            $stmt = $db->prepare("
                INSERT INTO encrypted_files 
                (file_id, file_name, file_size, encryption_key_hex, user_id, description) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $success = $stmt->execute([
                $fileId,
                $originalFilename,
                $fileData['size'],
                $encryptionKeyHex,
                $userId,
                $description
            ]);
            
            if (!$success) {
                throw new Exception("Gagal menyimpan data file ke database");
            }
            
            return [
                'file_id' => $fileId,
                'file_name' => $originalFilename,
                'encryption_key' => $encryptionKeyHex, // Return hex untuk ditampilkan ke user
                'original_size' => $fileData['size'],
                'encrypted_size' => $encryptInfo['encrypted_size']
            ];
            
        } catch (Exception $e) {
            // Clean up
            if (file_exists($tempPath)) unlink($tempPath);
            if (file_exists($encryptedPath)) unlink($encryptedPath);
            throw $e;
        }
    }
    
    public static function downloadDecryptedFile($fileId, $encryptionKeyHex) {
        $db = DatabaseConfig::getConnection();
        
        // Dapatkan info file
        $stmt = $db->prepare("
            SELECT * FROM encrypted_files 
            WHERE file_id = ?
        ");
        $stmt->execute([$fileId]);
        $fileInfo = $stmt->fetch();
        
        if (!$fileInfo) {
            throw new Exception("File tidak ditemukan");
        }
        
        // DEBUG: Log input key
        error_log("Input Key Hex: $encryptionKeyHex");
        error_log("Input Key Length: " . strlen($encryptionKeyHex));
        error_log("Stored Key Hex: " . $fileInfo['encryption_key_hex']);
        error_log("Stored Key Length: " . strlen($fileInfo['encryption_key_hex']));
        
        // Validasi key format
        if (strlen($encryptionKeyHex) !== 64 || !ctype_xdigit($encryptionKeyHex)) {
            throw new Exception("Format encryption key tidak valid. Harus 64 karakter hexadecimal.");
        }
        
        // Konversi key hex ke binary - PASTIKAN INI BENAR
        $encryptionKey = hex2bin($encryptionKeyHex);
        if ($encryptionKey === false) {
            throw new Exception("Gagal mengkonversi key hexadecimal");
        }
        
        // DEBUG: Log converted key
        error_log("Converted Key Length: " . strlen($encryptionKey));
        error_log("Converted Key Hex: " . bin2hex($encryptionKey));
        
        // Path file terenkripsi
        $encryptedPath = FILE_UPLOAD_DIR . $fileId . '.enc';
        if (!file_exists($encryptedPath)) {
            throw new Exception("File terenkripsi tidak ditemukan di server");
        }
        
        // Generate temporary output path
        $tempOutput = FILE_UPLOAD_DIR . 'temp_' . $fileId . '_' . time() . '_' . $fileInfo['file_name'];
        
        try {
            // Dekripsi file dengan key BINARY
            $decryptInfo = FileEncryptionEngine::decryptFile($encryptedPath, $tempOutput, $encryptionKey);
            
            // Update download count
            $updateStmt = $db->prepare("
                UPDATE encrypted_files 
                SET download_count = download_count + 1, 
                    last_downloaded = NOW() 
                WHERE file_id = ?
            ");
            $updateStmt->execute([$fileId]);
            
            return [
                'file_path' => $tempOutput,
                'file_name' => $fileInfo['file_name'],
                'file_size' => filesize($tempOutput)
            ];
            
        } catch (Exception $e) {
            if (file_exists($tempOutput)) {
                unlink($tempOutput);
            }
            throw $e;
        }
    }
    
    /**
     * Dapatkan semua file
     */
    public static function getAllFiles() {
        $db = DatabaseConfig::getConnection();
        $stmt = $db->prepare("
            SELECT file_id, file_name, file_size, download_count, 
                   last_downloaded, created_at, user_id, description 
            FROM encrypted_files 
            ORDER BY created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Dapatkan file oleh user tertentu
     */
    public static function getUserFiles($userId) {
        $db = DatabaseConfig::getConnection();
        $stmt = $db->prepare("
            SELECT file_id, file_name, file_size, download_count, 
                   last_downloaded, created_at, description 
            FROM encrypted_files 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
    
    /**
     * Hapus file
     */
    public static function deleteFile($fileId, $userId) {
        $db = DatabaseConfig::getConnection();
        
        // Hapus file terenkripsi dari server
        $encryptedPath = FILE_UPLOAD_DIR . $fileId . '.enc';
        if (file_exists($encryptedPath)) {
            unlink($encryptedPath);
        }
        
        // Hapus dari database
        $stmt = $db->prepare("
            DELETE FROM encrypted_files 
            WHERE file_id = ? AND user_id = ?
        ");
        return $stmt->execute([$fileId, $userId]);
    }
    
    /**
     * Dapatkan info file
     */
    public static function getFileInfo($fileId) {
        $db = DatabaseConfig::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM encrypted_files 
            WHERE file_id = ?
        ");
        $stmt->execute([$fileId]);
        return $stmt->fetch();
    }
}
?>