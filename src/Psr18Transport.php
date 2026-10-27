<?php

declare(strict_types=1);

namespace Hostily\Captcha;

use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;

/**
 * Optional transport on top of any PSR-18 client. The timeout is the job of the client you pass in
 * (PSR-18 has no per-request timeout), so configure it there.
 */
final class Psr18Transport implements Transport
{
    private $client;
    private $requests;
    private $streams;

    public function __construct(ClientInterface $client, RequestFactoryInterface $requests, StreamFactoryInterface $streams)
    {
        $this->client = $client;
        $this->requests = $requests;
        $this->streams = $streams;
    }

    public function postJson(string $url, string $json, float $timeout): array
    {
        $request = $this->requests->createRequest('POST', $url)
            ->withHeader('Content-Type', 'application/json')
            ->withHeader('Accept', 'application/json')
            ->withBody($this->streams->createStream($json));
        try {
            $response = $this->client->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            throw new UnavailableException('hostily Captcha not reachable: ' . $e->getMessage(), 0, $e);
        }

        return [$response->getStatusCode(), (string) $response->getBody()];
    }
}
