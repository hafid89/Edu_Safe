<?php
// pages/send_message.php
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/encryption_engine.php';
Auth::requireLogin();

// Validasi CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        header('Location: messaging.php?error=Invalid security token');
        exit();
    }
    
    $sender_id = $_SESSION['user_id'];
    $receiver_id = $_POST['receiver_id'] ?? 0;
    $message = trim($_POST['message'] ?? '');
    
    if (empty($message) || !$receiver_id) {
        header('Location: messaging.php?error=Invalid message data');
        exit();
    }
    
    try {
        $encryption_key = bin2hex(random_bytes(32));
        $encrypted_message = SuperEncryption::superEncrypt($message, $encryption_key);
        
        if (DatabaseHelper::saveMessage($sender_id, $receiver_id, $encrypted_message, $encryption_key)) {
            // Generate new CSRF token setelah sukses
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            header('Location: messaging.php?success=Message sent successfully');
        } else {
            header('Location: messaging.php?error=Failed to send message');
        }
    } catch (Exception $e) {
        header('Location: messaging.php?error=Encryption failed: ' . urlencode($e->getMessage()));
    }
    exit();
}

header('Location: messaging.php');
?>