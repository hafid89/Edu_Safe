<?php
// config/constants.php - FIXED VERSION

// Basic Configuration
define('SITE_NAME', 'edusafe');
define('SITE_URL', 'http://localhost/edusafe/');

// File Upload Settings
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 50 * 1024 * 1024); // 50MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png']);
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx', 'txt']);

// User Roles
define('DEFAULT_ROLE', 'mahasiswa');

// Encryption Key
define('ENCRYPTION_KEY', 'edusafe-secret-key-2024-256bit-aes');

// Session Configuration - HAPUS session_start() dari sini
ini_set('session.cookie_httponly', 1);
// ini_set('session.cookie_secure', 1);  // Tetap di-comment untuk localhost
ini_set('session.use_strict_mode', 1);

// HAPUS session_start() dari file ini!
?>