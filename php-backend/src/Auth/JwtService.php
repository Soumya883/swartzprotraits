<?php

declare(strict_types=1);

namespace App\Auth;

use RuntimeException;

final class JwtService
{
    public function __construct(
        private string $secret,
        private string $issuer
    ) {}

    public function issue(array $claims, int $ttlSeconds): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $now = time();
        $payload = array_merge($claims, [
            'iss' => $this->issuer,
            'iat' => $now,
            'exp' => $now + $ttlSeconds,
        ]);

        $baseHeader = $this->base64UrlEncode((string) json_encode($header));
        $basePayload = $this->base64UrlEncode((string) json_encode($payload));
        $signature = hash_hmac('sha256', $baseHeader . '.' . $basePayload, $this->secret, true);
        $baseSignature = $this->base64UrlEncode($signature);

        return $baseHeader . '.' . $basePayload . '.' . $baseSignature;
    }

    public function verify(string $token): array
    {
        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            throw new RuntimeException('Invalid token format');
        }

        [$baseHeader, $basePayload, $baseSignature] = $parts;
        $expected = $this->base64UrlEncode(
            hash_hmac('sha256', $baseHeader . '.' . $basePayload, $this->secret, true)
        );

        if (!hash_equals($expected, $baseSignature)) {
            throw new RuntimeException('Invalid token signature');
        }

        $payloadJson = $this->base64UrlDecode($basePayload);
        $payload = json_decode($payloadJson, true);
        if (!is_array($payload)) {
            throw new RuntimeException('Invalid token payload');
        }

        if (($payload['iss'] ?? null) !== $this->issuer) {
            throw new RuntimeException('Invalid token issuer');
        }

        if (($payload['exp'] ?? 0) < time()) {
            throw new RuntimeException('Token expired');
        }

        return $payload;
    }

    private function base64UrlEncode(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }

    private function base64UrlDecode(string $data): string
    {
        $padding = strlen($data) % 4;
        if ($padding > 0) {
            $data .= str_repeat('=', 4 - $padding);
        }

        return (string) base64_decode(strtr($data, '-_', '+/'));
    }
}
