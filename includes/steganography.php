<?php
// includes/steganography.php - SIMPLE LSB STEGANOGRAPHY
class Steganography {
    
    /**
     * Sembunyikan pesan dalam gambar menggunakan LSB
     */
    public static function hideMessage($image_path, $output_path, $message, $key) {
        // Validasi file
        if (!file_exists($image_path)) {
            throw new Exception("File gambar tidak ditemukan: $image_path");
        }
        
        // Cek tipe gambar
        $image_info = getimagesize($image_path);
        if ($image_info === false) {
            throw new Exception("File bukan gambar yang valid");
        }
        
        $image_type = $image_info[2];
        if ($image_type != IMAGETYPE_PNG && $image_type != IMAGETYPE_JPEG) {
            throw new Exception("Hanya format PNG dan JPEG yang didukung");
        }
        
        // Load gambar
        if ($image_type == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($image_path);
        } else {
            $image = imagecreatefrompng($image_path);
            imagealphablending($image, false);
            imagesavealpha($image, true);
        }
        
        if (!$image) {
            throw new Exception("Gagal memuat gambar");
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Enkripsi pesan sebelum disembunyikan
        $encrypted_message = self::encryptMessage($message, $key);
        
        // Format: [message_length(4bytes)][encrypted_message]
        $message_length = strlen($encrypted_message);
        $binary_data = self::intToBinary($message_length, 32) . self::stringToBinary($encrypted_message);
        
        $data_length = strlen($binary_data);
        $max_capacity = $width * $height * 3; // 3 channel per pixel
        
        if ($data_length > $max_capacity) {
            imagedestroy($image);
            throw new Exception("Pesan terlalu panjang. Maksimal: " . floor($max_capacity/8) . " karakter");
        }
        
        // Sisipkan data ke LSB
        $bit_index = 0;
        for($y = 0; $y < $height; $y++) {
            for($x = 0; $x < $width; $x++) {
                if ($bit_index >= $data_length) break 2;
                
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                $a = ($rgb >> 24) & 0xFF;
                
                // Sisipkan ke LSB masing-masing channel
                if ($bit_index < $data_length) {
                    $r = ($r & 0xFE) | intval($binary_data[$bit_index++]);
                }
                if ($bit_index < $data_length) {
                    $g = ($g & 0xFE) | intval($binary_data[$bit_index++]);
                }
                if ($bit_index < $data_length) {
                    $b = ($b & 0xFE) | intval($binary_data[$bit_index++]);
                }
                
                $new_color = imagecolorallocatealpha($image, $r, $g, $b, $a);
                if ($new_color === false) {
                    $new_color = imagecolorallocate($image, $r, $g, $b);
                }
                imagesetpixel($image, $x, $y, $new_color);
            }
        }
        
        // Simpan gambar
        if ($image_type == IMAGETYPE_JPEG) {
            $success = imagejpeg($image, $output_path, 100);
        } else {
            $success = imagepng($image, $output_path, 9);
        }
        
        imagedestroy($image);
        
        if (!$success) {
            throw new Exception("Gagal menyimpan gambar output");
        }
        
        return true;
    }
    
    /**
     * Ekstrak pesan dari gambar
     */
    public static function extractMessage($image_path, $key) {
        if (!file_exists($image_path)) {
            throw new Exception("File gambar tidak ditemukan: $image_path");
        }
        
        $image_info = getimagesize($image_path);
        if ($image_info === false) {
            throw new Exception("File bukan gambar yang valid");
        }
        
        $image_type = $image_info[2];
        if ($image_type == IMAGETYPE_JPEG) {
            $image = imagecreatefromjpeg($image_path);
        } else {
            $image = imagecreatefrompng($image_path);
        }
        
        if (!$image) {
            throw new Exception("Gagal memuat gambar");
        }
        
        $width = imagesx($image);
        $height = imagesy($image);
        
        // Ekstrak semua bit LSB
        $binary_data = "";
        for($y = 0; $y < $height; $y++) {
            for($x = 0; $x < $width; $x++) {
                $rgb = imagecolorat($image, $x, $y);
                $r = ($rgb >> 16) & 0xFF;
                $g = ($rgb >> 8) & 0xFF;
                $b = $rgb & 0xFF;
                
                $binary_data .= $r & 1;
                $binary_data .= $g & 1;
                $binary_data .= $b & 1;
            }
        }
        
        imagedestroy($image);
        
        // Baca 32 bit pertama sebagai panjang pesan
        if (strlen($binary_data) < 32) {
            throw new Exception("Data tidak cukup untuk ekstraksi");
        }
        
        $length_binary = substr($binary_data, 0, 32);
        $message_length = self::binaryToInt($length_binary);
        
        // Validasi panjang pesan
        if ($message_length <= 0 || $message_length > (strlen($binary_data) - 32) / 8) {
            throw new Exception("Panjang pesan tidak valid: $message_length");
        }
        
        // Ekstrak pesan terenkripsi
        $encrypted_binary = substr($binary_data, 32, $message_length * 8);
        $encrypted_message = self::binaryToString($encrypted_binary);
        
        if (strlen($encrypted_message) != $message_length) {
            throw new Exception("Panjang pesan tidak match");
        }
        
        // Dekripsi pesan
        return self::decryptMessage($encrypted_message, $key);
    }
    
    /**
     * Enkripsi pesan sederhana dengan XOR + Base64
     */
    private static function encryptMessage($message, $key) {
        $key = hash('sha256', $key, true); // Hash key untuk panjang konsisten
        $encrypted = '';
        
        for ($i = 0; $i < strlen($message); $i++) {
            $encrypted .= $message[$i] ^ $key[$i % strlen($key)];
        }
        
        return base64_encode($encrypted);
    }
    
    /**
     * Dekripsi pesan
     */
    private static function decryptMessage($encrypted_message, $key) {
        $key = hash('sha256', $key, true);
        $decoded = base64_decode($encrypted_message);
        $decrypted = '';
        
        for ($i = 0; $i < strlen($decoded); $i++) {
            $decrypted .= $decoded[$i] ^ $key[$i % strlen($key)];
        }
        
        return $decrypted;
    }
    
    /**
     * Konversi string ke binary
     */
    private static function stringToBinary($string) {
        $binary = '';
        for ($i = 0; $i < strlen($string); $i++) {
            $binary .= str_pad(decbin(ord($string[$i])), 8, '0', STR_PAD_LEFT);
        }
        return $binary;
    }
    
    /**
     * Konversi binary ke string
     */
    private static function binaryToString($binary) {
        $string = '';
        for ($i = 0; $i < strlen($binary); $i += 8) {
            $byte = substr($binary, $i, 8);
            if (strlen($byte) < 8) break;
            $string .= chr(bindec($byte));
        }
        return $string;
    }
    
    /**
     * Konversi integer ke binary (fixed length)
     */
    private static function intToBinary($number, $bits) {
        return str_pad(decbin($number), $bits, '0', STR_PAD_LEFT);
    }
    
    /**
     * Konversi binary ke integer
     */
    private static function binaryToInt($binary) {
        return bindec($binary);
    }
    
    /**
     * Hitung kapasitas maksimal gambar
     */
    public static function getMaxCapacity($image_path) {
        $image_info = getimagesize($image_path);
        if ($image_info === false) return 0;
        
        $width = $image_info[0];
        $height = $image_info[1];
        return floor(($width * $height * 3 - 32) / 8); // 32 bits untuk header
    }
}
?>