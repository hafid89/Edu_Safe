<?php
//includes/auth_file.php
session_start();
require_once '../../config/database_file.php';

class Auth {
    
    /**
     * Cek apakah user sudah login
     */
    public static function requireLogin() {
        if (!isset($_SESSION['user_id'])) {
            header('Location: ../login.php');
            exit;
        }
    }
    
    /**
     * Login user
     */
    public static function login($username, $password) {
        $db = DatabaseConfig::getConnection();
        
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            return true;
        }
        
        return false;
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        session_destroy();
        header('Location: ../login.php');
        exit;
    }
    
    /**
     * Cek role user
     */
    public static function isDosen() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'dosen';
    }
    
    /**
     * Cek role user
     */
    public static function isMahasiswa() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'mahasiswa';
    }
}
?>