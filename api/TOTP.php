<?php

class TOTP {
    
    private static $base32Map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    // Generate a random secret key (16 chars base32)
    public static function createSecret($length = 16) {
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= self::$base32Map[random_int(0, 31)];
        }
        return $secret;
    }

    // Calculate the code for a given secret and time slice
    public static function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretKey = self::base32Decode($secret);
        
        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        
        // HMAC-SHA1
        $hmac = hash_hmac('sha1', $time, $secretKey, true);
        
        // Get offset
        $offset = ord(substr($hmac, -1)) & 0x0F;
        
        // Extract 4 bytes
        $hashPart = substr($hmac, $offset, 4);
        
        // Unpack and mask
        $value = unpack('N', $hashPart);
        $value = $value[1];
        $value = $value & 0x7FFFFFFF;
        
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    // Verify a code
    public static function verifyCode($secret, $code, $discrepancy = 1, $currentTimeSlice = null) {
        if ($currentTimeSlice === null) {
            $currentTimeSlice = floor(time() / 30);
        }

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = self::getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        return false;
    }

    // Generate Google Authenticator QR URL (for SVG generation or text link)
    public static function getQRText($name, $secret, $issuer = null) {
        $data = "otpauth://totp/$name?secret=$secret";
        if ($issuer) {
            $data .= "&issuer=$issuer";
        }
        return $data;
    }
    
    // Helper: Base32 Decode
    private static function base32Decode($base32) {
        $base32 = strtoupper($base32);
        $l = strlen($base32);
        $n = 0;
        $j = 0;
        $binary = "";

        for ($i = 0; $i < $l; $i++) {
            $n = $n << 5;
            $n = $n + strpos(self::$base32Map, $base32[$i]);
            $j = $j + 5;
            if ($j >= 8) {
                $j = $j - 8;
                $binary .= chr(($n & (0xFF << $j)) >> $j);
            }
        }
        return $binary;
    }
}
?>