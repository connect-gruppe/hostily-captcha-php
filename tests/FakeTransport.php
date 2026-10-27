<?php

declare(strict_types=1);

namespace Hostily\Captcha\Tests;

use Hostily\Captcha\Transport;
use Hostily\Captcha\UnavailableException;

final class FakeTransport implements Transport
{
    public $calls = [];
    public $status = 200;
    public $body = '{"success":true,"punkte":0,"gruende":[],"hostname":"example.de"}';
    public $fail = false;

    public function postJson(string $url, string $json, float $timeout): array
    {
        $this->calls[] = ['url' => $url, 'json' => json_decode($json, true), 'raw' => $json, 'timeout' => $timeout];
        if ($this->fail) {
            throw new UnavailableException('down');
        }

        return [$this->status, $this->body];
    }
}
