<?php

use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\ImagickImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

class GoogleAuthenticator {
    private $secretLength = 16;
    private $base32Chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    public function createSecret() {
        $secret = '';
        $randomBytes = random_bytes($this->secretLength);
        for ($i = 0; $i < $this->secretLength; $i++) {
            $secret .= $this->base32Chars[ord($randomBytes[$i]) & 31];
        }
        return $secret;
    }

    public function getQRCodeUrl($label, $issuer, $secret) {
        return 'otpauth://totp/'.urlencode($label).'?secret='.$secret.'&issuer='.urlencode($issuer);
    }

    public function getQRCode($label, $issuer, $secret) {
        $qrUrl = $this->getQRCodeUrl($label, $issuer, $secret);
        
        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new ImagickImageBackEnd()
        );
        
        $writer = new Writer($renderer);
        
        $base64 = base64_encode($writer->writeString($qrUrl));
        
        return "
            <div>
                <img src='data:image/png;base64,{$base64}' alt='QR Code'>
            </div>
        ";
    }

    public static function verifyCode($secret, $code, $discrepancy = 1): bool
    {
        $authenticator = new self();
        return $authenticator->verify($secret, $code, $discrepancy);
    }

    public function verify($secret, $code, $discrepancy = 1) {
        if (strlen($code) != 6) {
            return false;
        }
        
        $timestamp = floor(time() / 30);
        
        for ($i = -$discrepancy; $i <= $discrepancy; $i++) {
            if ($this->calculateCode($secret, $timestamp + $i) == $code) {
                return true;
            }
        }
        return false;
    }

    private function base32Decode($secret) {
        $secret = strtoupper($secret);
        $buffer = 0;
        $bitsLeft = 0;
        $result = '';
        
        for ($i = 0; $i < strlen($secret); $i++) {
            $buffer <<= 5;
            $buffer |= strpos($this->base32Chars, $secret[$i]);
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $result .= chr(($buffer >> ($bitsLeft - 8)) & 0xFF);
                $bitsLeft -= 8;
            }
        }
        return $result;
    }

    private function calculateCode($secret, $timestamp) {
        $secretkey = $this->base32Decode($secret);
        
        $time = chr(0).chr(0).chr(0).chr(0).pack('N*', $timestamp);
        
        $hash = hash_hmac('SHA1', $time, $secretkey, true);
        
        $offset = ord(substr($hash, -1)) & 0x0F;
        $hashpart = substr($hash, $offset, 4);
        
        $value = unpack('N', $hashpart)[1];
        $value = $value & 0x7FFFFFFF;
        
        return str_pad($value % 1000000, 6, '0', STR_PAD_LEFT);
    }
}