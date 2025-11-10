<?php
// pages/logout.php
session_start();

// Hapus semua variabel sesi
$_SESSION = [];

// Hapus cookie sesi (opsional, untuk keamanan tambahan)
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Hancurkan sesi sepenuhnya
session_destroy();

// Redirect ke halaman login
header('Location: login.php');
exit();
?>
