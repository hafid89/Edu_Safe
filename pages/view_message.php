<?php
// pages/view_message.php
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/encryption_engine.php';
Auth::requireLogin();

$user_id = $_SESSION['user_id'];
$message_id = $_GET['id'] ?? 0;

// Get message details
$message = DatabaseHelper::getMessageById($message_id, $user_id);

// Cek apakah user adalah penerima pesan
if (!$message || $message['receiver_id'] != $user_id) {
    header('Location: messaging.php?error=Message not found or access denied');
    exit();
}

$decrypted_content = '';
$error = '';

try {
    // Dekripsi pesan
    $decrypted_content = SuperEncryption::superDecrypt(
        $message['encrypted_message'], 
        $message['encryption_key']
    );
} catch (Exception $e) {
    $error = "Failed to decrypt message: " . $e->getMessage();
}

// Tandai sebagai read (jika belum)
if (!$message['is_read']) {
    DatabaseHelper::markMessageAsRead($message_id, $user_id);
    $message['is_read'] = 1;
    $message['read_at'] = date('Y-m-d H:i:s');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Read Message - EduSafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; background: white; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); }
        .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; padding-bottom: 20px; border-bottom: 2px solid #eee; }
        .back-btn { background: #6c757d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; }
        .back-btn:hover { background: #5a6268; }
        .message-meta { background: #f8f9fa; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .encrypted-section, .decrypted-section { padding: 20px; border-radius: 5px; margin-bottom: 20px; }
        .encrypted-section { background: #fff3cd; border: 1px solid #ffeaa7; }
        .decrypted-section { background: #d1edff; border: 1px solid #a8d6ff; }
        .error { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 5px; margin-bottom: 20px; }
        .security-info { background: #d1ecf1; color: #0c5460; padding: 15px; border-radius: 5px; margin-top: 20px; }
        .encrypted-content { font-family: monospace; word-break: break-all; background: white; padding: 10px; border-radius: 3px; margin-top: 10px; }
        .decrypted-content { font-size: 1.1em; background: white; padding: 15px; border-radius: 3px; margin-top: 10px; white-space: pre-wrap; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔓 Read Secure Message</h1>
            <a href="messaging.php" class="back-btn">← Back to Messages</a>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <div class="message-meta">
            <strong>From:</strong> <?= htmlspecialchars($message['sender_name']) ?><br>
            <strong>To:</strong> You (<?= htmlspecialchars($_SESSION['username']) ?>)<br>
            <strong>Sent:</strong> <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?><br>
            <strong>Read:</strong> <?= date('M j, Y g:i A', strtotime($message['read_at'] ?? 'now')) ?>
        </div>

        <div class="encrypted-section">
            <h3>🔒 Encrypted Message (What was sent)</h3>
            <div class="encrypted-content">
                <?= htmlspecialchars($message['encrypted_message']) ?>
            </div>
            <p><small>This is the encrypted data that was transmitted and stored.</small></p>
        </div>

        <div class="decrypted-section">
            <h3>✅ Decrypted Message (What it says)</h3>
            <?php if ($decrypted_content): ?>
                <div class="decrypted-content">
                    <?= htmlspecialchars($decrypted_content) ?>
                </div>
                <p><small>This message has been successfully decrypted for you.</small></p>
            <?php else: ?>
                <div class="decrypted-content" style="background: #f8d7da;">
                    <em>Could not decrypt message</em>
                </div>
            <?php endif; ?>
        </div>

        <div class="security-info">
            <h4>🔐 Security Information</h4>
            <p>This message was protected with multiple encryption layers:</p>
            <ul>
                <li><strong>Affine Cipher:</strong> Character substitution encryption</li>
                <li><strong>Vigenere Cipher:</strong> Polyalphabetic substitution</li>
                <li><strong>AES-256-GCM:</strong> Military-grade encryption</li>
                <li><strong>Unique Key:</strong> Each message uses a different encryption key</li>
            </ul>
            <p><strong>Only you as the intended recipient can read this message.</strong></p>
        </div>
    </div>
</body>
</html>