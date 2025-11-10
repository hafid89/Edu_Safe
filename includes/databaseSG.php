<?php
// includes/databaseSG.php
require_once 'database.php'; // File database connection yang sudah ada

class DatabaseHelpers {
    
    /**
     * Simpan certificate dengan unique key
     */
    public static function saveCertificateWithUniqueKey($student_name, $course_name, $original_image, $stego_image, $user_id, $creator_name, $encryption_key) {
        $db = DatabaseConfig::getConnection();
        
        $stmt = $db->prepare("
            INSERT INTO certificates 
            (student_name, course_name, original_image_path, stego_image_path, created_by, creator_name, encryption_key, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        return $stmt->execute([
            $student_name, 
            $course_name, 
            $original_image, 
            $stego_image, 
            $user_id, 
            $creator_name, 
            $encryption_key
        ]);
    }
    
    /**
     * Dapatkan semua certificates
     */
    public static function getAllCertificates() {
        $db = DatabaseConfig::getConnection();
        $stmt = $db->prepare("
            SELECT c.*, u.username as creator_name 
            FROM certificates c 
            LEFT JOIN users u ON c.created_by = u.id 
            ORDER BY c.created_at DESC
        ");
        $stmt->execute();
        return $stmt->fetchAll();
    }
    
    /**
     * Dapatkan certificate by ID
     */
    public static function getCertificateById($id) {
        $db = DatabaseConfig::getConnection();
        $stmt = $db->prepare("SELECT * FROM certificates WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch();
    }
    
    /**
     * Dapatkan certificates by creator
     */
    public static function getCertificatesByCreator($user_id) {
        $db = DatabaseConfig::getConnection();
        $stmt = $db->prepare("
            SELECT * FROM certificates 
            WHERE created_by = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll();
    }
    
    /**
     * Dapatkan encryption key untuk certificate
     */
    public static function getEncryptionKey($certificate_id, $user_id) {
        $db = DatabaseConfig::getConnection();
        $stmt = $db->prepare("
            SELECT encryption_key FROM certificates 
            WHERE id = ? AND created_by = ?
        ");
        $stmt->execute([$certificate_id, $user_id]);
        $result = $stmt->fetch();
        return $result ? $result['encryption_key'] : null;
    }
}
?>