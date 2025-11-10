<?php
// debug_encryption.php - FILE DEBUG KOMPREHENSIF
require_once 'includes/database.php';
require_once 'includes/file_encryption_engine.php';
require_once 'config/file_encryption_config.php';

echo "<h1>🔍 DEBUG ENCRYPTION SYSTEM</h1>";
echo "<pre>";

// 1. CEK KONEKSI DATABASE
echo "=== 1. DATABASE CONNECTION ===\n";
try {
    $db = DatabaseConfig::getConnection();
    echo "✅ Database connected successfully\n";
} catch (Exception $e) {
    echo "❌ Database error: " . $e->getMessage() . "\n";
    exit;
}

// 2. CEK FILE DI DATABASE
echo "\n=== 2. FILE INFO FROM DATABASE ===\n";
$file_id = 'ujian_690e04aedc78a1.06453588';
try {
    $stmt = $db->prepare("SELECT * FROM encrypted_files WHERE file_id = ?");
    $stmt->execute([$file_id]);
    $file_info = $stmt->fetch();
    
    if ($file_info) {
        echo "✅ File found in database:\n";
        echo "   File ID: " . $file_info['file_id'] . "\n";
        echo "   File Name: " . $file_info['file_name'] . "\n";
        echo "   File Size: " . $file_info['file_size'] . " bytes\n";
        echo "   User ID: " . $file_info['user_id'] . "\n";
        echo "   Encrypted Key: " . $file_info['encrypted_key'] . "\n";
        echo "   Key Length: " . strlen($file_info['encrypted_key']) . " characters\n";
        
        // Validasi key length
        if (strlen($file_info['encrypted_key']) !== 64) {
            echo "❌ KEY LENGTH INVALID! Should be 64, got: " . strlen($file_info['encrypted_key']) . "\n";
        } else {
            echo "✅ Key length valid (64 characters)\n";
        }
        
        // Validasi hex format
        if (!ctype_xdigit($file_info['encrypted_key'])) {
            echo "❌ KEY FORMAT INVALID! Not hexadecimal\n";
        } else {
            echo "✅ Key format valid (hexadecimal)\n";
        }
        
    } else {
        echo "❌ File not found in database\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Database query error: " . $e->getMessage() . "\n";
    exit;
}

// 3. CEK FILE ENCRYPTED DI SERVER
echo "\n=== 3. ENCRYPTED FILE CHECK ===\n";
$encrypted_path = 'uploads/encrypted_files/' . $file_id . '.encrypted';
if (file_exists($encrypted_path)) {
    echo "✅ Encrypted file exists: $encrypted_path\n";
    echo "   File size: " . filesize($encrypted_path) . " bytes\n";
    
    // Cek header file encrypted
    $encrypted_content = file_get_contents($encrypted_path, false, null, 0, 100);
    echo "   File header (hex): " . bin2hex($encrypted_content) . "\n";
    
    // Coba parse header
    $header_end = strpos($encrypted_content, "\n");
    if ($header_end !== false) {
        $header = substr($encrypted_content, 0, $header_end);
        $file_info_header = json_decode($header, true);
        echo "   File header (parsed): " . print_r($file_info_header, true) . "\n";
    } else {
        echo "❌ Invalid encrypted file format - no header found\n";
    }
    
} else {
    echo "❌ Encrypted file not found: $encrypted_path\n";
    exit;
}

// 4. CEK ALGORITMA SUPPORT
echo "\n=== 4. ENCRYPTION ALGORITHM CHECK ===\n";
$supported = FileEncryptionEngine::isSupported();
echo "ChaCha20-Poly1305 supported: " . ($supported ? "✅ YES" : "❌ NO") . "\n";

$cipher_methods = openssl_get_cipher_methods();
echo "Available cipher methods:\n";
foreach ($cipher_methods as $method) {
    if (strpos($method, 'chacha') !== false) {
        echo "   - $method\n";
    }
}

// 5. TEST DECRYPTION DENGAN KEY DARI DATABASE
echo "\n=== 5. DECRYPTION TEST ===\n";
$key_from_db = $file_info['encrypted_key'];
echo "Using key from database: $key_from_db\n";

try {
    // Convert key to binary
    $key_bin = hex2bin($key_from_db);
    echo "Key binary length: " . strlen($key_bin) . " bytes\n";
    
    // Output path untuk test
    $test_output = 'uploads/encrypted_files/debug_test_output.docx';
    
    echo "Starting decryption...\n";
    $start_time = microtime(true);
    
    $result = FileEncryptionEngine::decryptFile($encrypted_path, $test_output, $key_bin);
    
    $end_time = microtime(true);
    $duration = round(($end_time - $start_time) * 1000, 2);
    
    echo "✅ DECRYPTION SUCCESS!\n";
    echo "   Original size: " . $result['original_size'] . " bytes\n";
    echo "   Algorithm: " . $result['algorithm'] . "\n";
    echo "   Duration: " . $duration . " ms\n";
    echo "   Output file: $test_output\n";
    echo "   Output file exists: " . (file_exists($test_output) ? "YES" : "NO") . "\n";
    echo "   Output file size: " . (file_exists($test_output) ? filesize($test_output) : 0) . " bytes\n";
    
} catch (Exception $e) {
    echo "❌ DECRYPTION FAILED!\n";
    echo "   Error: " . $e->getMessage() . "\n";
    
    // Debug lebih detail
    echo "   Debug info:\n";
    echo "   - Key length: " . strlen($key_from_db) . " chars\n";
    echo "   - Key valid hex: " . (ctype_xdigit($key_from_db) ? "YES" : "NO") . "\n";
    echo "   - Encrypted file readable: " . (is_readable($encrypted_path) ? "YES" : "NO") . "\n";
    
    // Coba baca file encrypted untuk debug
    try {
        $encrypted_data = file_get_contents($encrypted_path);
        echo "   - Encrypted data length: " . strlen($encrypted_data) . " bytes\n";
        
        // Parse manual untuk cek struktur
        $header_end = strpos($encrypted_data, "\n");
        if ($header_end !== false) {
            $header = substr($encrypted_data, 0, $header_end);
            $encrypted_content = substr($encrypted_data, $header_end + 1);
            
            echo "   - Header length: " . strlen($header) . " bytes\n";
            echo "   - Encrypted content length: " . strlen($encrypted_content) . " bytes\n";
            
            // Cek bagian-bagian
            if (strlen($encrypted_content) >= 24 + 16 + 64) {
                $nonce = substr($encrypted_content, 0, 24);
                $tag = substr($encrypted_content, 24, 16);
                $hmac = substr($encrypted_content, 40, 64);
                $encrypted_data_content = substr($encrypted_content, 104);
                
                echo "   - Nonce length: " . strlen($nonce) . " bytes\n";
                echo "   - Tag length: " . strlen($tag) . " bytes\n";
                echo "   - HMAC length: " . strlen($hmac) . " bytes\n";
                echo "   - Encrypted data length: " . strlen($encrypted_data_content) . " bytes\n";
            } else {
                echo "   ❌ Encrypted content too short\n";
            }
        } else {
            echo "   ❌ No header found in encrypted file\n";
        }
    } catch (Exception $e2) {
        echo "   - Error reading encrypted file: " . $e2->getMessage() . "\n";
    }
}

// 6. TEST DENGAN KEY MANUAL (JIKA DECRYPTION GAGAL)
echo "\n=== 6. MANUAL KEY TEST ===\n";
$manual_key = '3afced878b7df605404ce0200fb98d3ab423c30a3d7230bedf779ef6cd998458';
echo "Testing with manual key: $manual_key\n";

if ($manual_key !== $key_from_db) {
    echo "❌ MANUAL KEY DIFFERENT FROM DATABASE KEY!\n";
    echo "   Database: $key_from_db\n";
    echo "   Manual:   $manual_key\n";
    
    // Test dengan manual key
    try {
        $key_bin = hex2bin($manual_key);
        $test_output2 = 'uploads/encrypted_files/debug_test_output2.docx';
        
        $result = FileEncryptionEngine::decryptFile($encrypted_path, $test_output2, $key_bin);
        echo "✅ MANUAL KEY DECRYPTION SUCCESS!\n";
        echo "   This means the key in database is WRONG\n";
        
    } catch (Exception $e) {
        echo "❌ MANUAL KEY ALSO FAILED: " . $e->getMessage() . "\n";
    }
} else {
    echo "✅ Manual key matches database key\n";
}

echo "\n=== DEBUG COMPLETE ===\n";
echo "</pre>";
?>