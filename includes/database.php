<?php
// includes/database.php - VERSI LENGKAP
require_once __DIR__ . '/../config/database.php';

class DatabaseHelper {
    // USER METHODS
    public static function getUserById($id) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("SELECT id, username, role, created_at FROM users WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    public static function getAllUsers($exclude_id = null) {
        $pdo = DatabaseConfig::getConnection();
        if ($exclude_id) {
            $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE id != ? AND status = 'active'");
            $stmt->execute([$exclude_id]);
        } else {
            $stmt = $pdo->prepare("SELECT id, username, role FROM users WHERE status = 'active'");
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
    
    // MESSAGE METHODS
    public static function saveMessage($sender_id, $receiver_id, $encrypted_message, $encryption_key) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, encrypted_message, encryption_key) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$sender_id, $receiver_id, $encrypted_message, $encryption_key]);
    }
    
    public static function getMessages($user_id) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, u1.username as sender_name, u2.username as receiver_name 
            FROM messages m 
            LEFT JOIN users u1 ON m.sender_id = u1.id 
            LEFT JOIN users u2 ON m.receiver_id = u2.id 
            WHERE m.sender_id = ? OR m.receiver_id = ? 
            ORDER BY m.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id]);
        return $stmt->fetchAll();
    }
    
    public static function getMessageById($message_id, $user_id) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, u1.username as sender_name, u2.username as receiver_name 
            FROM messages m 
            LEFT JOIN users u1 ON m.sender_id = u1.id 
            LEFT JOIN users u2 ON m.receiver_id = u2.id 
            WHERE m.id = ? AND (m.sender_id = ? OR m.receiver_id = ?)
        ");
        $stmt->execute([$message_id, $user_id, $user_id]);
        return $stmt->fetch();
    }
    
    public static function getConversation($user1_id, $user2_id) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("
            SELECT m.*, u1.username as sender_name, u2.username as receiver_name 
            FROM messages m 
            LEFT JOIN users u1 ON m.sender_id = u1.id 
            LEFT JOIN users u2 ON m.receiver_id = u2.id 
            WHERE (m.sender_id = ? AND m.receiver_id = ?) 
               OR (m.sender_id = ? AND m.receiver_id = ?)
            ORDER BY m.created_at ASC
        ");
        $stmt->execute([$user1_id, $user2_id, $user2_id, $user1_id]);
        return $stmt->fetchAll();
    }
    
    public static function markMessageAsRead($message_id, $reader_id) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("UPDATE messages SET is_read = 1, read_at = NOW() WHERE id = ? AND receiver_id = ?");
        return $stmt->execute([$message_id, $reader_id]);
    }
    
    public static function getUnreadCount($user_id) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("SELECT COUNT(*) as unread_count FROM messages WHERE receiver_id = ? AND is_read = 0");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['unread_count'] ?? 0;
    }
    
    // FILE METHODS
    public static function saveExamFile($filename, $original_filename, $encrypted_content, $uploaded_by) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("INSERT INTO exam_files (filename, original_filename, encrypted_content, uploaded_by) VALUES (?, ?, ?, ?)");
        return $stmt->execute([$filename, $original_filename, $encrypted_content, $uploaded_by]);
    }
    
    public static function getExamFiles($user_id = null) {
        $pdo = DatabaseConfig::getConnection();
        if ($user_id) {
            $stmt = $pdo->prepare("
                SELECT ef.*, u.username as uploader_name 
                FROM exam_files ef 
                LEFT JOIN users u ON ef.uploaded_by = u.id 
                WHERE ef.uploaded_by = ? 
                ORDER BY ef.created_at DESC
            ");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT ef.*, u.username as uploader_name 
                FROM exam_files ef 
                LEFT JOIN users u ON ef.uploaded_by = u.id 
                ORDER BY ef.created_at DESC
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
    
    public static function getExamFileById($file_id, $user_id = null) {
        $pdo = DatabaseConfig::getConnection();
        if ($user_id) {
            $stmt = $pdo->prepare("
                SELECT ef.*, u.username as uploader_name 
                FROM exam_files ef 
                LEFT JOIN users u ON ef.uploaded_by = u.id 
                WHERE ef.id = ? AND ef.uploaded_by = ?
            ");
            $stmt->execute([$file_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT ef.*, u.username as uploader_name 
                FROM exam_files ef 
                LEFT JOIN users u ON ef.uploaded_by = u.id 
                WHERE ef.id = ?
            ");
            $stmt->execute([$file_id]);
        }
        return $stmt->fetch();
    }
    
    // CERTIFICATE METHODS
    public static function saveCertificate($student_name, $course_name, $original_image_path, $stego_image_path, $created_by) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("INSERT INTO certificates (student_name, course_name, original_image_path, stego_image_path, created_by) VALUES (?, ?, ?, ?, ?)");
        return $stmt->execute([$student_name, $course_name, $original_image_path, $stego_image_path, $created_by]);
    }
    
    public static function getCertificates($user_id = null) {
        $pdo = DatabaseConfig::getConnection();
        if ($user_id) {
            $stmt = $pdo->prepare("
                SELECT c.*, u.username as creator_name 
                FROM certificates c 
                LEFT JOIN users u ON c.created_by = u.id 
                WHERE c.created_by = ? 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.*, u.username as creator_name 
                FROM certificates c 
                LEFT JOIN users u ON c.created_by = u.id 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute();
        }
        return $stmt->fetchAll();
    }
    
    public static function getCertificateById($certificate_id, $user_id = null) {
        $pdo = DatabaseConfig::getConnection();
        if ($user_id) {
            $stmt = $pdo->prepare("
                SELECT c.*, u.username as creator_name 
                FROM certificates c 
                LEFT JOIN users u ON c.created_by = u.id 
                WHERE c.id = ? AND c.created_by = ?
            ");
            $stmt->execute([$certificate_id, $user_id]);
        } else {
            $stmt = $pdo->prepare("
                SELECT c.*, u.username as creator_name 
                FROM certificates c 
                LEFT JOIN users u ON c.created_by = u.id 
                WHERE c.id = ?
            ");
            $stmt->execute([$certificate_id]);
        }
        return $stmt->fetch();
    }

       public static function getAllCertificates() {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("
            SELECT c.*, u.username as creator_name 
            FROM certificates c 
            LEFT JOIN users u ON c.created_by = u.id 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
            // Tambahkan function baru di DatabaseHelper
        public static function saveCertificateWithKey($student_name, $course_name, $original_image, $stego_image, $creator_id, $encryption_key) {
            $db = DatabaseConfig::getConnection();
            $stmt = $db->prepare("INSERT INTO certificates (student_name, course_name, original_image_path, stego_image_path, creator_id, encryption_key, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
            return $stmt->execute([$student_name, $course_name, $original_image, $stego_image, $creator_id, $encryption_key]);
        }

        public static function getCertificatesWithKeys($user_id) {
            $db = DatabaseConfig::getConnection();
            $stmt = $db->prepare("
                SELECT c.*, u.username as creator_name 
                FROM certificates c 
                JOIN users u ON c.creator_id = u.id 
                WHERE c.creator_id = ? 
                ORDER BY c.created_at DESC
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        public static function saveCertificateWithUniqueKey($student_name, $course_name, $original_image, $stego_image, $creator_id, $creator_name, $encryption_key) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("INSERT INTO certificates 
            (student_name, course_name, original_image_path, stego_image_path, created_by, creator_name, encryption_key, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())");
        return $stmt->execute([
            $student_name, 
            $course_name, 
            $original_image, 
            $stego_image, 
            $creator_id,
            $creator_name,
            $encryption_key
        ]);
    }

    // ✅ METHOD BARU: Get certificates by creator (untuk lihat keys)
    public static function getCertificatesByCreator($creator_id) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("
            SELECT c.*, u.username as creator_name 
            FROM certificates c 
            LEFT JOIN users u ON c.created_by = u.id 
            WHERE c.created_by = ? 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute([$creator_id]);
        return $stmt->fetchAll();
    }

    // ✅ METHOD BARU: Verify creator name untuk extraction
    public static function verifyCertificateCreator($certificate_id, $creator_name) {
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("
            SELECT COUNT(*) as match_count 
            FROM certificates 
            WHERE id = ? AND creator_name = ?
        ");
        $stmt->execute([$certificate_id, $creator_name]);
        $result = $stmt->fetch();
        return $result['match_count'] > 0;
    }
}
?>