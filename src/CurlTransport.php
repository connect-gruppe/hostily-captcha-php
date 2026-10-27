<?php

declare(strict_types=1);

namespace Hostily\Captcha;

/** Default transport based on ext-curl. */
final class CurlTransport implements Transport
{
    public function postJson(string $url, string $json, float $timeout): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new UnavailableException('curl_init failed');
        }
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $json,
            CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json', 'User-Agent: hostily-captcha-php/0.1.0'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT_MS => (int) max(1, $timeout * 1000),
            CURLOPT_TIMEOUT_MS => (int) max(1, $timeout * 1000),
            CURLOPT_FOLLOWLOCATION => false,
        ]);
        $body = curl_exec($ch);
        if ($body === false) {
            $err = curl_error($ch);
            self::close($ch);
            throw new UnavailableException('hostily Captcha not reachable: ' . $err);
        }
        $status = (int) curl_getinfo($ch, CURLINFO_RESPONSE_CODE);
        self::close($ch);

        return [$status, (string) $body];
    }

    /** curl_close() has had no effect since PHP 8.0 and is deprecated in 8.5 (its notice would corrupt the page). */
    private static function close($ch): void
    {
        if (PHP_VERSION_ID < 80000) {
            curl_close($ch);
        }
    }
}
