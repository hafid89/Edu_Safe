<?php
//icludes/file_encryption_engine.php
class FileEncryptionEngine {
    
    private static $ALGORITHM = 'aes-256-gcm';
    private static $HMAC_ALGORITHM = 'sha512';
    private static $IV_SIZE = 12;
    private static $TAG_SIZE = 16;
    private static $HMAC_SIZE = 64;
    
    /**
     * Enkripsi file dengan AES-256-GCM + HMAC-SHA512
     */
    public static function encryptFile($inputPath, $outputPath, $key) {
        if (!file_exists($inputPath)) {
            throw new Exception("❌ File tidak ditemukan: $inputPath");
        }
        
        // Generate random IV
        $iv = random_bytes(self::$IV_SIZE);
        
        // Baca file content
        $fileContent = file_get_contents($inputPath);
        if ($fileContent === false) {
            throw new Exception("❌ Gagal membaca file: $inputPath");
        }
        
        // DEBUG: Log info enkripsi
        error_log("=== ENCRYPTION START ===");
        error_log("Encrypting file: $inputPath");
        error_log("Key length: " . strlen($key));
        error_log("Key hex: " . bin2hex($key));
        error_log("File size: " . strlen($fileContent));
        error_log("IV: " . bin2hex($iv));
        
        // Variabel untuk menampung tag
        $tag = '';
        
        // Enkripsi dengan AES-256-GCM - PERBAIKAN: gunakan approach yang berbeda
        $encrypted = openssl_encrypt(
            $fileContent,
            self::$ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag,
            '',
            16
        );
        
        if ($encrypted === false) {
            $error = openssl_error_string();
            error_log("Encryption failed: $error");
            throw new Exception("❌ Enkripsi gagal: $error");
        }
        
        error_log("Encrypted data length: " . strlen($encrypted));
        error_log("Tag length: " . strlen($tag));
        error_log("Tag: " . bin2hex($tag));
        
        // Jika tag masih kosong, coba approach alternatif
        if (strlen($tag) === 0) {
            error_log("WARNING: Tag is empty, trying alternative approach");
            
            // Coba dengan cara manual
            $ciphertext = openssl_encrypt(
                $fileContent,
                self::$ALGORITHM,
                $key,
                OPENSSL_RAW_DATA,
                $iv
            );
            
            if ($ciphertext === false) {
                throw new Exception("❌ Enkripsi gagal: " . openssl_error_string());
            }
            
            // Untuk GCM, kita perlu extract tag dari ciphertext
            // Dalam beberapa implementasi, tag ada di akhir ciphertext
            $encrypted = substr($ciphertext, 0, -self::$TAG_SIZE);
            $tag = substr($ciphertext, -self::$TAG_SIZE);
            
            error_log("Alternative - Encrypted length: " . strlen($encrypted));
            error_log("Alternative - Tag length: " . strlen($tag));
            error_log("Alternative - Tag: " . bin2hex($tag));
        }
        
        // Pastikan tag tidak kosong
        if (strlen($tag) !== self::$TAG_SIZE) {
            throw new Exception("❌ Tag authentication tidak valid");
        }
        
        // HMAC untuk autentikasi (iv + tag + encrypted data)
        $dataToHmac = $iv . $tag . $encrypted;
        $hmac = hash_hmac(self::$HMAC_ALGORITHM, $dataToHmac, $key, true);
        
        error_log("Data for HMAC length: " . strlen($dataToHmac));
        error_log("HMAC: " . bin2hex($hmac));
        
        // Format output: [iv(12)][tag(16)][hmac(64)][encrypted_data]
        $outputData = $iv . $tag . $hmac . $encrypted;
        
        if (file_put_contents($outputPath, $outputData) === false) {
            throw new Exception("❌ Gagal menyimpan file terenkripsi: $outputPath");
        }
        
        // DEBUG: Log success
        error_log("Encryption successful: $outputPath");
        error_log("Original size: " . strlen($fileContent));
        error_log("Encrypted size: " . strlen($outputData));
        error_log("=== ENCRYPTION END ===");
        
        return [
            'original_size' => strlen($fileContent),
            'encrypted_size' => strlen($outputData),
            'algorithm' => self::$ALGORITHM
        ];
    }
    
    /**
     * Dekripsi file dengan AES-256-GCM + HMAC-SHA512
     */
    public static function decryptFile($inputPath, $outputPath, $key) {
        if (!file_exists($inputPath)) {
            throw new Exception("❌ File terenkripsi tidak ditemukan: $inputPath");
        }
        
        // Baca file terenkripsi
        $encryptedData = file_get_contents($inputPath);
        if ($encryptedData === false) {
            throw new Exception("❌ Gagal membaca file terenkripsi: $inputPath");
        }
        
        // DEBUG: Log info dekripsi
        error_log("=== DECRYPTION START ===");
        error_log("Decrypting file: $inputPath");
        error_log("Key length: " . strlen($key));
        error_log("Key hex: " . bin2hex($key));
        error_log("Encrypted file size: " . strlen($encryptedData));
        
        // Parse bagian-bagian encrypted data
        $expected_min_size = self::$IV_SIZE + self::$TAG_SIZE + self::$HMAC_SIZE;
        if (strlen($encryptedData) < $expected_min_size) {
            error_log("File too small: " . strlen($encryptedData) . " < $expected_min_size");
            throw new Exception("❌ File terenkripsi corrupt");
        }
        
        $iv = substr($encryptedData, 0, self::$IV_SIZE);
        $tag = substr($encryptedData, self::$IV_SIZE, self::$TAG_SIZE);
        $hmac = substr($encryptedData, self::$IV_SIZE + self::$TAG_SIZE, self::$HMAC_SIZE);
        $encryptedDataContent = substr($encryptedData, self::$IV_SIZE + self::$TAG_SIZE + self::$HMAC_SIZE);
        
        // DEBUG: Log parsed components
        error_log("IV: " . bin2hex($iv));
        error_log("Tag: " . bin2hex($tag));
        error_log("Tag length: " . strlen($tag));
        error_log("HMAC: " . bin2hex($hmac));
        error_log("Encrypted content size: " . strlen($encryptedDataContent));
        
        // HMAC verification
        $dataToVerify = $iv . $tag . $encryptedDataContent;
        $calculatedHmac = hash_hmac(self::$HMAC_ALGORITHM, $dataToVerify, $key, true);
        
        error_log("Data for HMAC verification length: " . strlen($dataToVerify));
        error_log("Calculated HMAC: " . bin2hex($calculatedHmac));
        error_log("Stored HMAC: " . bin2hex($hmac));
        error_log("HMAC match: " . (hash_equals($hmac, $calculatedHmac) ? "YES" : "NO"));
        
        if (!hash_equals($hmac, $calculatedHmac)) {
            error_log("HMAC VERIFICATION FAILED");
            throw new Exception("❌ HMAC verification failed - file mungkin dimodifikasi atau key salah");
        }
        
        // Dekripsi konten
        $decrypted = openssl_decrypt(
            $encryptedDataContent,
            self::$ALGORITHM,
            $key,
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );
        
        if ($decrypted === false) {
            $error = openssl_error_string();
            error_log("Decryption failed: $error");
            throw new Exception("❌ Dekripsi gagal: $error");
        }
        
        // Simpan file terdekripsi
        if (file_put_contents($outputPath, $decrypted) === false) {
            throw new Exception("❌ Gagal menyimpan file terdekripsi: $outputPath");
        }
        
        // DEBUG: Log success
        error_log("Decryption successful: $outputPath");
        error_log("Decrypted size: " . strlen($decrypted));
        error_log("=== DECRYPTION END ===");
        
        return [
            'decrypted_size' => strlen($decrypted)
        ];
    }
    
    /**
     * Generate encryption key (32 bytes = 256-bit)
     */
    public static function generateKey() {
        return random_bytes(32);
    }
    
    /**
     * Validasi AES-256-GCM support
     */
    public static function isSupported() {
        return in_array(self::$ALGORITHM, openssl_get_cipher_methods());
    }
}
?>