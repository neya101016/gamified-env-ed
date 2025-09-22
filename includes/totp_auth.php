<?php
/**
 * TOTP Authentication Helper
 * Implements time-based one-time password algorithm similar to authenticator apps
 */
class TOTPAuth {
    // Default time step size in seconds (30 seconds is standard for TOTP)
    private $timeStep = 30;
    
    // Default OTP length
    private $codeLength = 6;
    
    // Validity window (how many time steps to check before/after current time)
    private $window = 1;
    
    /**
     * Generate a new secret key
     * @return string Base32 encoded secret key
     */
    public function generateSecretKey($length = 16) {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        
        $randBytes = random_bytes($length);
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[ord($randBytes[$i]) & 31];
        }
        
        return $secret;
    }
    
    /**
     * Generate a time-based OTP
     * @param string $secretKey Base32 encoded secret key
     * @param int $timestamp Timestamp for which to generate OTP (default: current time)
     * @return string The generated OTP
     */
    public function generateOTP($secretKey, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // Calculate counter value based on time step
        $counter = floor($timestamp / $this->timeStep);
        
        // Decode secret key from base32
        $secretKey = $this->base32Decode($secretKey);
        
        // Pack counter into binary string (64-bit, big-endian)
        $counterBin = pack('N*', 0, $counter);
        
        // Generate HMAC-SHA1 hash
        $hash = hash_hmac('sha1', $counterBin, $secretKey, true);
        
        // Get offset based on last nibble
        $offset = ord($hash[19]) & 0x0F;
        
        // Extract 4 bytes from hash starting at offset
        $hashPart = substr($hash, $offset, 4);
        
        // Convert to 32-bit integer, ignore MSB (make positive)
        $value = unpack('N', $hashPart)[1] & 0x7FFFFFFF;
        
        // Generate code by modulo and padding
        $code = $value % pow(10, $this->codeLength);
        
        // Pad with leading zeros if necessary
        return str_pad($code, $this->codeLength, '0', STR_PAD_LEFT);
    }
    
    /**
     * Verify a TOTP code
     * @param string $secretKey Base32 encoded secret key
     * @param string $code The OTP code to verify
     * @param int $timestamp Timestamp for verification (default: current time)
     * @return bool True if code is valid, false otherwise
     */
    public function verifyOTP($secretKey, $code, $timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        // Normalize the code (remove spaces, etc.)
        $code = preg_replace('/\s+/', '', $code);
        
        // Check codes within window range
        for ($i = -$this->window; $i <= $this->window; $i++) {
            $checkTime = $timestamp + ($i * $this->timeStep);
            $expectedCode = $this->generateOTP($secretKey, $checkTime);
            
            if (hash_equals($expectedCode, $code)) {
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Get remaining seconds until the current OTP expires
     * @param int $timestamp Current timestamp (default: current time)
     * @return int Seconds until expiration
     */
    public function getRemainingSeconds($timestamp = null) {
        if ($timestamp === null) {
            $timestamp = time();
        }
        
        $secondsInCurrentStep = $timestamp % $this->timeStep;
        return $this->timeStep - $secondsInCurrentStep;
    }
    
    /**
     * Set the time step (period) for the TOTP
     * @param int $seconds Time step in seconds
     */
    public function setTimeStep($seconds) {
        $this->timeStep = $seconds;
    }
    
    /**
     * Set the code length for the TOTP
     * @param int $length Length of the OTP code
     */
    public function setCodeLength($length) {
        $this->codeLength = $length;
    }
    
    /**
     * Set the window size for verification
     * @param int $window Number of time steps to check before/after
     */
    public function setWindow($window) {
        $this->window = $window;
    }
    
    /**
     * Decode a Base32 string
     * @param string $base32String The Base32 encoded string
     * @return string The decoded binary string
     */
    private function base32Decode($base32String) {
        $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $base32String = strtoupper($base32String);
        $binary = '';
        $buffer = 0;
        $bitsLeft = 0;
        
        for ($i = 0; $i < strlen($base32String); $i++) {
            $char = $base32String[$i];
            if ($char == '=') continue; // Padding
            
            $charValue = strpos($base32Chars, $char);
            if ($charValue === false) continue; // Invalid character
            
            // Add 5 bits to buffer
            $buffer = ($buffer << 5) | $charValue;
            $bitsLeft += 5;
            
            // Extract full bytes from buffer
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $binary .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }
        
        return $binary;
    }
}
?>