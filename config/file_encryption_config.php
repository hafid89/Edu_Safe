<?php
// Konfigurasi Enkripsi File
define('FILE_ENCRYPTION_ALGORITHM', 'ChaCha20-Poly1305');
define('FILE_HMAC_ALGORITHM', 'SHA512');
define('FILE_KEY_LENGTH', 32);
define('FILE_NONCE_SIZE', 12);
define('FILE_TAG_SIZE', 16);
define('FILE_MAX_SIZE', 100 * 1024 * 1024); // 100MB
define('FILE_UPLOAD_DIR', '../uploads/encrypted_files/');

// Buat folder upload jika belum ada
if (!is_dir(FILE_UPLOAD_DIR)) {
    mkdir(FILE_UPLOAD_DIR, 0755, true);
}
?>