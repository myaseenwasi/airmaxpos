<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class CardknoxSignatureVerifier
{
    private $publicKeys = [];

    public function verifySignature($data, $signatureHeader)
    {
        try {
            // Parse the header: keyId=..., signature=..., algorithm=...
            $signatureParts = explode(',', $signatureHeader);
            $parts = [];
            foreach ($signatureParts as $part) {
                if (strpos($part, '=') !== false) {
                    list($key, $value) = explode('=', $part, 2);
                    $parts[trim($key)] = trim($value);
                }
            }

            // Required fields
            if (!isset($parts['keyId']) || !isset($parts['signature']) || !isset($parts['algorithm'])) {
                Log::error('Missing required signature fields', ['parts' => $parts]);
                return false;
            }

            $keyId = $parts['keyId'];
            $signatureB64 = $parts['signature'];
            $algorithm = $parts['algorithm'];

            // Get public key (from cache or download)
            $publicKey = $this->getPublicKey($keyId);
            if (!$publicKey) {
                return false;
            }

            // Decode the base64 URL-safe signature
            $signatureB64 = strtr($signatureB64, '-_', '+/');
            $signatureB64 .= str_repeat('=', (4 - strlen($signatureB64) % 4) % 4);
            $signatureBytes = base64_decode($signatureB64);

            // Verify signature
            if ($algorithm !== 'ES256') {
                Log::error('Unsupported algorithm', ['algorithm' => $algorithm]);
                return false;
            }

            return openssl_verify(
                $data,
                $signatureBytes,
                $publicKey,
                OPENSSL_ALGO_SHA256
            ) === 1;

        } catch (\Exception $e) {
            Log::error('Signature verification error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    private function getPublicKey($keyId)
    {
        // Check cache first
        $cacheKey = "cardknox_public_key_{$keyId}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            // Download public key
            $response = Http::get("https://cdn.cardknox.com/.well-known/device/{$keyId}.pem");
            if (!$response->successful()) {
                Log::error('Failed to download public key', [
                    'keyId' => $keyId,
                    'status' => $response->status()
                ]);
                return false;
            }

            $publicKey = $response->body();
            
            // Cache the public key for 24 hours
            Cache::put($cacheKey, $publicKey, now()->addHours(24));
            
            return $publicKey;

        } catch (\Exception $e) {
            Log::error('Error getting public key', [
                'keyId' => $keyId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
} 