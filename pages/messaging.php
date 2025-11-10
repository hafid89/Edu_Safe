<?php
// pages/messaging.php
require_once '../includes/auth.php';
require_once '../includes/database.php';
require_once '../includes/encryption_engine.php';
Auth::requireLogin();

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Send message
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_message'])) {
    $receiver_id = $_POST['receiver_id'] ?? '';
    $message = trim($_POST['message'] ?? '');
    
    if (empty($receiver_id) || empty($message)) {
        $error = "Please select a receiver and enter a message";
    } else {
        try {
            $encryption_key = bin2hex(random_bytes(32));
            $encrypted_message = SuperEncryption::superEncrypt($message, $encryption_key);
            
            if (DatabaseHelper::saveMessage($user_id, $receiver_id, $encrypted_message, $encryption_key)) {
                $success = "Message sent successfully!";
            } else {
                $error = "Failed to send message";
            }
        } catch (Exception $e) {
            $error = "Encryption failed: " . $e->getMessage();
        }
    }
}

// Get users and messages
$users = DatabaseHelper::getAllUsers($user_id);
$messages = DatabaseHelper::getMessages($user_id);
$unread_count = DatabaseHelper::getUnreadCount($user_id);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Secure Messaging - EduSafe</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; padding: 20px; }
        .header { background: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .nav { margin-bottom: 20px; }
        .nav a { color: #667eea; text-decoration: none; margin-right: 15px; }
        .content { display: grid; grid-template-columns: 1fr 2fr; gap: 20px; }
        .form-section, .messages-section { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .form-group { margin-bottom: 15px; }
        .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
        .form-group input, .form-group select, .form-group textarea { width: 100%; padding: 10px; border: 2px solid #ddd; border-radius: 5px; }
        .form-group textarea { height: 100px; resize: vertical; }
        .btn { background: #667eea; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
        .btn:hover { background: #764ba2; }
        .btn-read { background: #28a745; padding: 8px 15px; font-size: 0.9em; text-decoration: none; display: inline-block; border-radius: 5px; }
        .btn-read:hover { background: #218838; }
        .error { background: #f8d7da; color: #721c24; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .success { background: #d4edda; color: #155724; padding: 10px; border-radius: 5px; margin-bottom: 15px; }
        .message { border: 1px solid #ddd; padding: 15px; margin-bottom: 10px; border-radius: 5px; }
        .message-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
        .message-sender { font-weight: bold; color: #667eea; }
        .message-time { color: #666; font-size: 0.9em; }
        .message-content { margin-top: 10px; }
        .unread-badge { background: #dc3545; color: white; padding: 2px 8px; border-radius: 10px; font-size: 0.7em; margin-left: 10px; }
        .encrypted-content { background: #f8f9fa; padding: 10px; border-radius: 5px; margin-top: 10px; font-family: monospace; word-break: break-all; }
        .message-info { background: #e9ecef; padding: 10px; border-radius: 5px; margin-top: 10px; font-size: 0.9em; }
        .sent-message { border-left: 4px solid #667eea; }
        .received-message { border-left: 4px solid #28a745; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔒 Secure Messaging <?php if ($unread_count > 0): ?><span class="unread-badge"><?= $unread_count ?> new</span><?php endif; ?></h1>
            <div class="nav">
                <a href="index.php">← Back to Dashboard</a>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="error"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="success"><?= htmlspecialchars($success) ?></div>
        <?php endif; ?>

        <div class="content">
            <div class="form-section">
                <h3>Send Encrypted Message</h3>
                <form method="POST">
                    <div class="form-group">
                        <label for="receiver_id">To:</label>
                        <select name="receiver_id" id="receiver_id" required>
                            <option value="">Select User</option>
                            <?php foreach ($users as $user): ?>
                                <option value="<?= $user['id'] ?>"><?= htmlspecialchars($user['username']) ?> (<?= $user['role'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="message">Plain Text Message:</label>
                        <textarea name="message" id="message" placeholder="Type your message in plain text here..." required></textarea>
                    </div>
                    <button type="submit" name="send_message" class="btn">Encrypt & Send Message</button>
                </form>
                
                <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 5px;">
                    <h4>🔐 Encryption Process</h4>
                    <p><small>Your message will be protected with:<br>
                    • Affine Cipher<br>
                    • Vigenere Cipher<br>
                    • AES-256-GCM<br>
                    • Unique key for each message</small></p>
                    <p><strong>Only the intended recipient can decrypt and read your message.</strong></p>
                </div>
            </div>

            <div class="messages-section">
                <h3>Message History</h3>
                <?php if (empty($messages)): ?>
                    <p>No messages yet. Start a conversation!</p>
                <?php else: ?>
                    <?php foreach ($messages as $message): ?>
                        <div class="message <?= $message['sender_id'] == $user_id ? 'sent-message' : 'received-message' ?>">
                            <div class="message-header">
                                <span class="message-sender">
                                    <?php if ($message['sender_id'] == $user_id): ?>
                                        ➡️ <strong>You</strong> to <?= htmlspecialchars($message['receiver_name']) ?>
                                    <?php else: ?>
                                        ⬅️ From <strong><?= htmlspecialchars($message['sender_name']) ?></strong>
                                        <?php if (!$message['is_read']): ?>
                                            <span class="unread-badge">NEW</span>
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </span>
                                <span class="message-time">
                                    <?= date('M j, Y g:i A', strtotime($message['created_at'])) ?>
                                </span>
                            </div>
                            
                            <div class="message-content">
                                <?php if ($message['sender_id'] == $user_id): ?>
                                    <!-- TAMPILAN PENGIRIM -->
                                    <div class="encrypted-content">
                                        <strong>Encrypted Message:</strong><br>
                                        <code><?= htmlspecialchars(substr($message['encrypted_message'], 0, 100)) ?>...</code>
                                    </div>
                                    <div class="message-info">
                                        <strong>Status:</strong> 
                                        <?= $message['is_read'] ? '✅ Read by recipient' : '📨 Sent (not read yet)' ?>
                                        <br>
                                        <small>Only <?= htmlspecialchars($message['receiver_name']) ?> can decrypt this message</small>
                                    </div>
                                <?php else: ?>
                                    <!-- TAMPILAN PENERIMA -->
                                    <div class="encrypted-content">
                                        <strong>Encrypted Message Received:</strong><br>
                                        <code><?= htmlspecialchars(substr($message['encrypted_message'], 0, 100)) ?>...</code>
                                    </div>
                                    <div class="message-info">
                                        <?php if ($message['is_read']): ?>
                                            <strong>Status:</strong> ✅ Read<br>
                                            <a href="view_message.php?id=<?= $message['id'] ?>" class="btn-read">
                                                🔓 View Decrypted Message
                                            </a>
                                        <?php else: ?>
                                            <strong>Status:</strong> 🔒 Encrypted<br>
                                            <a href="view_message.php?id=<?= $message['id'] ?>" class="btn-read">
                                                🔐 Read This Message
                                            </a>
                                        <?php endif; ?>
                                        <br>
                                        <small>Click to decrypt and read the message</small>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</body>
</html>