<?php
//pages/mahasiswa/test_encryption.php
require_once '../../includes/file_encryption_engine.php';
require_once '../../includes/file_manager.php';
require_once '../../config/file_encryption_config.php';

echo "<h2>Testing Encryption/Decryption</h2>";

// Test 1: Check algorithm support
echo "<h3>1. Algorithm Support</h3>";
echo "ChaCha20-Poly1305 supported: " . (FileEncryptionEngine::isSupported() ? "YES" : "NO") . "<br>";

// Test 2: Generate and verify key
echo "<h3>2. Key Generation</h3>";
$key = FileEncryptionEngine::generateKey();
$keyHex = bin2hex($key);
echo "Generated Key (hex): $keyHex<br>";
echo "Key length: " . strlen($key) . " bytes<br>";

// Test 3: Test conversion
echo "<h3>3. Key Conversion Test</h3>";
$convertedKey = hex2bin($keyHex);
echo "Original == Converted: " . ($key === $convertedKey ? "YES" : "NO") . "<br>";

// Test 4: Simple encryption/decryption test
echo "<h3>4. Encryption/Decryption Test</h3>";
$testData = "Hello, World! This is a test message.";
$testFile = FILE_UPLOAD_DIR . 'test_original.txt';
$encryptedFile = FILE_UPLOAD_DIR . 'test_encrypted.enc';
$decryptedFile = FILE_UPLOAD_DIR . 'test_decrypted.txt';

// Create test file
file_put_contents($testFile, $testData);

try {
    // Encrypt
    $encryptInfo = FileEncryptionEngine::encryptFile($testFile, $encryptedFile, $key);
    echo "Encryption successful<br>";
    echo "Original size: {$encryptInfo['original_size']}<br>";
    echo "Encrypted size: {$encryptInfo['encrypted_size']}<br>";
    
    // Decrypt
    $decryptInfo = FileEncryptionEngine::decryptFile($encryptedFile, $decryptedFile, $key);
    echo "Decryption successful<br>";
    echo "Decrypted size: {$decryptInfo['decrypted_size']}<br>";
    
    // Verify
    $decryptedData = file_get_contents($decryptedFile);
    echo "Data matches: " . ($testData === $decryptedData ? "YES" : "NO") . "<br>";
    
    // Clean up
    unlink($testFile);
    unlink($encryptedFile);
    unlink($decryptedFile);
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "<br>";
}

echo "<h3>5. Available Cipher Methods</h3>";
$ciphers = openssl_get_cipher_methods();
echo "Total ciphers: " . count($ciphers) . "<br>";
echo "ChaCha20 related:<br>";
foreach ($ciphers as $cipher) {
    if (stripos($cipher, 'chacha') !== false) {
        echo "- $cipher<br>";
    }
}
?>