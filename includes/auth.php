<?php
// includes/auth.php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/constants.php';
// Di bagian atas auth.php tambahkan:
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

class Auth {
     public static function hashPassword($password) {
        // Hanya gunakan Argon2id, jangan pakai SHA3-512
        $options = [
            'memory_cost' => 65536,
            'time_cost' => 4,
            'threads' => 2
        ];
        return password_hash($password, PASSWORD_ARGON2ID, $options);
    }
    
    public static function verifyPassword($password, $stored_hash) {
        // Gunakan password_verify standar
        return password_verify($password, $stored_hash);
    }
    
    public static function login($username, $password) {
        // Pastikan session started
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $pdo = DatabaseConfig::getConnection();
        $stmt = $pdo->prepare("SELECT id, username, password_hash, role FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && self::verifyPassword($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['login_time'] = time();
            
            // Update last login
            $update_stmt = $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $update_stmt->execute([$user['id']]);
            
            return true;
        }
        return false;
    }
    
    public static function register($username, $password, $role = 'mahasiswa') {
        $pdo = DatabaseConfig::getConnection();
        
        // Check if username exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        if ($check_stmt->fetch()) {
            throw new Exception("Username already exists");
        }
        
        $hashed_password = self::hashPassword($password);
        
        $stmt = $pdo->prepare("INSERT INTO users (username, password_hash, role, status) VALUES (?, ?, ?, 'active')");
        return $stmt->execute([$username, $hashed_password, $role]);
    }
    
    public static function isLoggedIn() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    public static function logout() {
        session_destroy();
        session_start();
    }
    
    public static function requireLogin() {
        if (!self::isLoggedIn()) {
            header('Location: login.php');
            exit();
        }
    }
    
    public static function requireRole($role) {
        self::requireLogin();
        if ($_SESSION['role'] !== $role) {
            header('Location: dashboard.php');
            exit();
        }
    }
}
?>