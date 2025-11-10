<?php
// includes/encryption_engine.php
require_once __DIR__ . '/../config/encryption.php';

class SuperEncryption {
    // Affine Cipher Encryption
    public static function affineEncrypt($text, $a = 5, $b = 8) {
        $result = "";
        $m = 26; // Panjang alfabet
        
        foreach (str_split($text) as $char) {
            if (ctype_alpha($char)) {
                $is_upper = ctype_upper($char);
                $char = strtolower($char);
                $x = ord($char) - ord('a');
                $encrypted = ($a * $x + $b) % $m;
                $encrypted_char = chr($encrypted + ord('a'));
                $result .= $is_upper ? strtoupper($encrypted_char) : $encrypted_char;
            } else {
                $result .= $char;
            }
        }
        return $result;
    }
    
    // Affine Cipher Decryption
    public static function affineDecrypt($text, $a = 5, $b = 8) {
        $result = "";
        $m = 26;
        // Cari inverse modular dari a
        $a_inv = 0;
        for ($i = 0; $i < $m; $i++) {
            if (($a * $i) % $m == 1) {
                $a_inv = $i;
                break;
            }
        }
        
        foreach (str_split($text) as $char) {
            if (ctype_alpha($char)) {
                $is_upper = ctype_upper($char);
                $char = strtolower($char);
                $y = ord($char) - ord('a');
                $decrypted = ($a_inv * ($y - $b + $m)) % $m;
                $decrypted_char = chr($decrypted + ord('a'));
                $result .= $is_upper ? strtoupper($decrypted_char) : $decrypted_char;
            } else {
                $result .= $char;
            }
        }
        return $result;
    }
    
    // Vigenere Cipher Encryption
    public static function vigenereEncrypt($text, $key) {
        $result = "";
        $key_length = strlen($key);
        $key_index = 0;
        
        foreach (str_split($text) as $char) {
            if (ctype_alpha($char)) {
                $is_upper = ctype_upper($char);
                $char = strtolower($char);
                $shift = ord($key[$key_index % $key_length]) - ord('a');
                $encrypted = chr(((ord($char) - ord('a') + $shift) % 26) + ord('a'));
                $result .= $is_upper ? strtoupper($encrypted) : $encrypted;
                $key_index++;
            } else {
                $result .= $char;
            }
        }
        return $result;
    }
    
    // Vigenere Cipher Decryption
    public static function vigenereDecrypt($text, $key) {
        $result = "";
        $key_length = strlen($key);
        $key_index = 0;
        
        foreach (str_split($text) as $char) {
            if (ctype_alpha($char)) {
                $is_upper = ctype_upper($char);
                $char = strtolower($char);
                $shift = ord($key[$key_index % $key_length]) - ord('a');
                $decrypted = chr(((ord($char) - ord('a') - $shift + 26) % 26) + ord('a'));
                $result .= $is_upper ? strtoupper($decrypted) : $decrypted;
                $key_index++;
            } else {
                $result .= $char;
            }
        }
        return $result;
    }
    
    // Super Encryption: Affine + Vigenere + AES-256
     // Super Encryption: Affine + Vigenere + AES-256 - DIPERBAIKI
    public static function superEncrypt($text, $key) {
        if (empty($text)) return '';
        
        // Step 1: Affine Cipher - gunakan key yang diturunkan dari $key
        $affine_key = self::deriveAffineKey($key);
        $affine_encrypted = self::affineEncrypt($text, $affine_key['a'], $affine_key['b']);
        
        // Step 2: Vigenere Cipher - gunakan key yang sama
        $vigenere_key = self::deriveVigenereKey($key);
        $vigenere_encrypted = self::vigenereEncrypt($affine_encrypted, $vigenere_key);
        
        // Step 3: AES-256-GCM
        $iv = EncryptionConfig::generateIV();
        $tag = '';
        $aes_key = self::deriveAESKey($key); // Pastikan key cocok untuk AES
        
        $encrypted = openssl_encrypt(
            $vigenere_encrypted, 
            EncryptionConfig::METHOD, 
            $aes_key, 
            OPENSSL_RAW_DATA, 
            $iv, 
            $tag,
            '',
            EncryptionConfig::TAG_LENGTH
        );
        
        if ($encrypted === false) {
            throw new Exception("AES encryption failed: " . openssl_error_string());
        }
        
        return base64_encode($iv . $tag . $encrypted);
    }
    
    // Super Decryption - DIPERBAIKI
    public static function superDecrypt($encrypted_text, $key) {
        if (empty($encrypted_text)) return '';
        
        $data = base64_decode($encrypted_text);
        if (strlen($data) < (EncryptionConfig::IV_LENGTH + EncryptionConfig::TAG_LENGTH)) {
            throw new Exception("Invalid encrypted data format");
        }
        
        $iv = substr($data, 0, EncryptionConfig::IV_LENGTH);
        $tag = substr($data, EncryptionConfig::IV_LENGTH, EncryptionConfig::TAG_LENGTH);
        $ciphertext = substr($data, EncryptionConfig::IV_LENGTH + EncryptionConfig::TAG_LENGTH);
        
        // Step 1: AES Decrypt
        $aes_key = self::deriveAESKey($key);
        $aes_decrypted = openssl_decrypt(
            $ciphertext, 
            EncryptionConfig::METHOD, 
            $aes_key, 
            OPENSSL_RAW_DATA, 
            $iv, 
            $tag
        );
        
        if ($aes_decrypted === false) {
            throw new Exception("AES decryption failed: " . openssl_error_string());
        }
        
        // Step 2: Vigenere Decrypt
        $vigenere_key = self::deriveVigenereKey($key);
        $vigenere_decrypted = self::vigenereDecrypt($aes_decrypted, $vigenere_key);
        
        // Step 3: Affine Decrypt
        $affine_key = self::deriveAffineKey($key);
        $affine_decrypted = self::affineDecrypt($vigenere_decrypted, $affine_key['a'], $affine_key['b']);
        
        return $affine_decrypted;
    }
    
    // Fungsi untuk menurunkan key yang konsisten
    private static function deriveAffineKey($key) {
        $hash = hash('sha256', $key . 'affine', true);
        $a = (ord($hash[0]) % 12) * 2 + 1; // a harus coprime dengan 26
        $b = ord($hash[1]) % 26;
        return ['a' => $a, 'b' => $b];
    }
    
    private static function deriveVigenereKey($key) {
        $hash = hash('sha256', $key . 'vigenere', true);
        $base_key = '';
        for ($i = 0; $i < 16; $i++) {
            $base_key .= chr(ord($hash[$i]) % 26 + ord('a'));
        }
        return $base_key;
    }
    
    private static function deriveAESKey($key) {
        return hash('sha256', $key . 'aes', true);
    }

}

class DatabaseEncryption {
    public static function encryptData($data, $key) {
        if (in_array('camellia-256-cbc', openssl_get_cipher_methods())) {
            $method = 'camellia-256-cbc';
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
            $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
            return base64_encode($iv . $encrypted);
        } else {
            // Fallback ke AES-256
            $method = 'aes-256-cbc';
            $iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length($method));
            $encrypted = openssl_encrypt($data, $method, $key, 0, $iv);
            return base64_encode($iv . $encrypted);
        }
    }
    
    public static function decryptData($encrypted_data, $key) {
        $data = base64_decode($encrypted_data);
        
        if (in_array('camellia-256-cbc', openssl_get_cipher_methods())) {
            $iv_length = openssl_cipher_iv_length('camellia-256-cbc');
            $method = 'camellia-256-cbc';
        } else {
            $iv_length = openssl_cipher_iv_length('aes-256-cbc');
            $method = 'aes-256-cbc';
        }
        
        $iv = substr($data, 0, $iv_length);
        $ciphertext = substr($data, $iv_length);
        
        return openssl_decrypt($ciphertext, $method, $key, 0, $iv);
    }
}
?>