<?php
/**
 * Lightweight PHP TOTP (Google Authenticator) Helper Class
 * Based on standard RFC 6238 implementation.
 */
class GoogleAuthenticator {
    // 32 allowed Base32 characters
    private $_base32Map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function createSecret($secretLength = 16) {
        $validChars = $this->_base32Map;
        $secret = '';
        for ($i = 0; $i < $secretLength; $i++) {
            $secret .= $validChars[rand(0, 31)];
        }
        return $secret;
    }

    public function getQRCodeUrl($name, $secret, $title = null) {
        $urlencoded = urlencode('otpauth://totp/' . $name . '?secret=' . $secret . ($title ? '&issuer=' . urlencode($title) : ''));
        return 'https://api.qrserver.com/v1/create-qr-code/?data=' . $urlencoded . '&size=200x200&ecc=M';
    }

    public function verifyCode($secret, $code, $discrepancy = 1) {
        $currentTimeSlice = floor(time() / 30);

        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            $calculatedCode = $this->getCode($secret, $currentTimeSlice + $i);
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }
        return false;
    }

    public function getCode($secret, $timeSlice = null) {
        if ($timeSlice === null) {
            $timeSlice = floor(time() / 30);
        }

        $secretkey = $this->base32Decode($secret);
        
        // Pack time into binary string
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timeSlice);
        
        // Hash it with SHA1
        $hm = hash_hmac('SHA1', $time, $secretkey, true);
        
        // Use last nibble of result as index/offset
        $offset = ord(substr($hm, -1)) & 0x0F;
        
        // Grab 4 bytes of the result
        $hashpart = substr($hm, $offset, 4);
        
        // Unpack binary value
        $value = unpack('N', $hashpart);
        $value = $value[1];
        
        // Drop MSB
        $value = $value & 0x7FFFFFFF;
        
        // Calculate modulo to get a 6 digit code
        $modulo = pow(10, 6);
        return str_pad($value % $modulo, 6, '0', STR_PAD_LEFT);
    }

    private function base32Decode($secret) {
        if (empty($secret)) return '';
        
        $base32chars = $this->_base32Map;
        $base32charsFlipped = array_flip(str_split($base32chars));

        $paddingCharCount = substr_count($secret, $base32chars[32] ?? '=');
        $allowedValues = [6, 4, 3, 1, 0];
        if (!in_array($paddingCharCount, $allowedValues)) return false;
        
        for ($i = 0; $i < 4; $i++) {
            if ($paddingCharCount == $allowedValues[$i] &&
                substr($secret, -($allowedValues[$i])) != str_repeat($base32chars[32] ?? '=', $allowedValues[$i])) {
                return false;
            }
        }
        
        $secret = str_replace('=', '', $secret);
        $secret = str_split($secret);
        $binaryString = "";
        
        for ($i = 0; $i < count($secret); $i = $i + 8) {
            $x = "";
            if (!in_array($secret[$i], str_split($base32chars))) return false;
            
            for ($j = 0; $j < 8; $j++) {
                if (isset($secret[$i + $j])) {
                    $x .= str_pad(base_convert($base32charsFlipped[$secret[$i + $j]], 10, 2), 5, '0', STR_PAD_LEFT);
                }
            }
            $eightBits = str_split($x, 8);
            for ($z = 0; $z < count($eightBits); $z++) {
                if (isset($eightBits[$z]) && strlen($eightBits[$z]) == 8) {
                    $binaryString .= (($y = chr(base_convert($eightBits[$z], 2, 10))) || ord($y) == 48) ? $y : "";
                }
            }
        }
        return $binaryString;
    }
}
