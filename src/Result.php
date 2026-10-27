<?php

declare(strict_types=1);

namespace Hostily\Captcha;

/** Outcome of one verification. */
final class Result
{
    /** @var bool */
    public $success;
    /** @var string[] short codes, e.g. token_bereits_verwendet, honeypot, mindestzeit */
    public $gruende;
    /** @var int|float|null spam score (only with form content) */
    public $punkte;
    /** @var string[] reCAPTCHA-style error codes, e.g. invalid-input-secret */
    public $errorCodes;
    /** @var string */
    public $hostname;
    /** @var string|null */
    public $challengeTs;

    public function __construct(bool $success, array $gruende = [], $punkte = null, array $errorCodes = [], string $hostname = '', ?string $challengeTs = null)
    {
        $this->success = $success;
        $this->gruende = $gruende;
        $this->punkte = $punkte;
        $this->errorCodes = $errorCodes;
        $this->hostname = $hostname;
        $this->challengeTs = $challengeTs;
    }

    /** @param array<string,mixed> $data decoded service answer */
    public static function fromArray(array $data): self
    {
        return new self(
            ($data['success'] ?? false) === true,
            array_values(array_map('strval', (array) ($data['gruende'] ?? []))),
            $data['punkte'] ?? null,
            array_values(array_map('strval', (array) ($data['error-codes'] ?? []))),
            (string) ($data['hostname'] ?? ''),
            isset($data['challenge_ts']) ? (string) $data['challenge_ts'] : null
        );
    }

    /** True when the secret was missing or wrong: a configuration error, not a spam verdict. */
    public function isConfigurationError(): bool
    {
        return (bool) array_intersect($this->errorCodes, ['missing-input-secret', 'invalid-input-secret']);
    }
}
