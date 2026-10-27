<?php

declare(strict_types=1);

namespace Hostily\Captcha;

/**
 * Client for hostily Captcha: POST /api/v1/pruefen.
 *
 *   $captcha = new Client(getenv('HOSTILY_CAPTCHA_SECRET'));
 *   $result = $captcha->verifyForm($_POST, $_SERVER['REMOTE_ADDR'] ?? null);
 *   if (!$result->success) { ... reject ... }
 *
 * verify*() throw UnavailableException when the service cannot be reached.
 */
final class Client
{
    public const DEFAULT_ENDPOINT = 'https://captcha.hostily.de';

    private $secret;
    private $endpoint;
    private $timeout;
    private $transport;

    /**
     * @param string $secret   project secret (server side only, never in the browser)
     * @param float  $timeout  seconds for connect and total
     */
    public function __construct(string $secret, string $endpoint = self::DEFAULT_ENDPOINT, float $timeout = 5.0, ?Transport $transport = null)
    {
        $this->secret = $secret;
        $this->endpoint = rtrim($endpoint, '/');
        $this->timeout = $timeout;
        $this->transport = $transport ?? new CurlTransport();
    }

    /**
     * @param string|null          $token    value of the field hostily-captcha-token
     * @param string|null          $remoteip IP address of the visitor
     * @param array<string,mixed>|null $formular form content (use Form::content() - never password fields)
     * @throws UnavailableException
     */
    public function verify(?string $token, ?string $remoteip = null, ?array $formular = null): Result
    {
        if ($token === null || trim($token) === '') {
            return new Result(false, ['token_fehlt'], null, ['missing-input-response']);
        }
        $body = ['secret' => $this->secret, 'response' => trim($token)];
        if ($remoteip !== null && $remoteip !== '') {
            $body['remoteip'] = $remoteip;
        }
        if ($formular !== null) {
            $body['formular'] = $formular === [] ? new \stdClass() : $formular;
        }
        $json = json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            throw new \InvalidArgumentException('Form content cannot be encoded as JSON: ' . json_last_error_msg());
        }

        [$status, $raw] = $this->transport->postJson($this->endpoint . '/api/v1/pruefen', $json, $this->timeout);
        $data = json_decode($raw, true);
        if ($status >= 500 || $status === 429 || !is_array($data) || !array_key_exists('success', $data)) {
            throw new UnavailableException('hostily Captcha answered unusably (HTTP ' . $status . ')');
        }

        return Result::fromArray($data);
    }

    /**
     * Take token (and optionally the content) out of a form array and verify.
     *
     * @param array<string,mixed>|null $post      defaults to $_POST
     * @param bool                     $sendContent also send the form content for rule/honeypot checks
     * @throws UnavailableException
     */
    public function verifyForm(?array $post = null, ?string $remoteip = null, bool $sendContent = true): Result
    {
        $post = $post ?? $_POST;

        return $this->verify(Form::token($post), $remoteip, $sendContent ? Form::content($post) : null);
    }
}
