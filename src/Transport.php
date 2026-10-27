<?php

declare(strict_types=1);

namespace Hostily\Captcha;

interface Transport
{
    /**
     * POST a JSON body.
     *
     * @return array{0:int,1:string} HTTP status and response body
     * @throws UnavailableException on network errors and timeouts
     */
    public function postJson(string $url, string $json, float $timeout): array;
}
