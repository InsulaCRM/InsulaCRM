<?php

namespace App\Services;

class TotpService
{
    /**
     * Generate a random base32 secret.
     */
    public function generateSecret(int $length = 32): string
    {
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $secret = '';
        for ($i = 0; $i < $length; $i++) {
            $secret .= $chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Verify a TOTP code against a secret.
     */
    public function verify(string $secret, string $code, int $window = 1): bool
    {
        $timestamp = floor(time() / 30);

        for ($i = -$window; $i <= $window; $i++) {
            if ($this->generateCode($secret, $timestamp + $i) === $code) {
                return true;
            }
        }

        return false;
    }

    /**
     * Generate a TOTP code for a given timestamp counter.
     */
    private function generateCode(string $secret, int $counter): string
    {
        $binary = pack('N*', 0) . pack('N*', $counter);
        $hash = hash_hmac('sha1', $binary, $this->base32Decode($secret), true);
        $offset = ord($hash[19]) & 0x0F;
        $code = (
            ((ord($hash[$offset]) & 0x7F) << 24) |
            ((ord($hash[$offset + 1]) & 0xFF) << 16) |
            ((ord($hash[$offset + 2]) & 0xFF) << 8) |
            (ord($hash[$offset + 3]) & 0xFF)
        ) % 1000000;

        return str_pad((string) $code, 6, '0', STR_PAD_LEFT);
    }

    /**
     * Get the otpauth:// URI for QR code generation.
     */
    public function getQrUri(string $secret, string $email, string $issuer = 'InsulaCRM'): string
    {
        return sprintf(
            'otpauth://totp/%s:%s?secret=%s&issuer=%s&digits=6&period=30',
            rawurlencode($issuer),
            rawurlencode($email),
            $secret,
            rawurlencode($issuer)
        );
    }

    /**
     * Generate recovery codes.
     */
    public function generateRecoveryCodes(int $count = 8): array
    {
        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $codes[] = strtoupper(bin2hex(random_bytes(4))) . '-' . strtoupper(bin2hex(random_bytes(4)));
        }
        return $codes;
    }

    /**
     * Decode a base32 string.
     */
    private function base32Decode(string $input): string
    {
        $map = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
        $input = strtoupper(rtrim($input, '='));
        $buffer = 0;
        $bitsLeft = 0;
        $output = '';

        for ($i = 0, $len = strlen($input); $i < $len; $i++) {
            $val = strpos($map, $input[$i]);
            if ($val === false) {
                continue;
            }
            $buffer = ($buffer << 5) | $val;
            $bitsLeft += 5;
            if ($bitsLeft >= 8) {
                $bitsLeft -= 8;
                $output .= chr(($buffer >> $bitsLeft) & 0xFF);
            }
        }

        return $output;
    }
}
