<?php

declare(strict_types=1);

namespace Hostily\Captcha;

/** Helpers to take the token and the content out of a submitted form. */
final class Form
{
    public const TOKEN_FIELDS = ['hostily-captcha-token', 'g-recaptcha-response'];

    private const SENSITIVE = '/pass(word|wort|wd|phrase|code)|(^|[_\-\[])pass($|[_\-\]])|pwd|secret|csrf|xsrf|token|cvc|cvv/i';

    /**
     * Token from a form array ($_POST by default) - field hostily-captcha-token, else g-recaptcha-response.
     *
     * @param array<string,mixed>|null $post
     */
    public static function token(?array $post = null): ?string
    {
        $post = $post ?? $_POST;
        foreach (self::TOKEN_FIELDS as $name) {
            if (isset($post[$name]) && is_string($post[$name]) && trim($post[$name]) !== '') {
                return trim($post[$name]);
            }
        }

        return null;
    }

    /**
     * The content to hand to the service for rules and honeypot checks: token fields and
     * everything that looks like a password, secret, CSRF token or card code are removed (also in nested arrays).
     *
     * @param array<string,mixed> $post
     * @param string[]            $exclude additional field names to leave out
     * @return array<string,mixed>
     */
    public static function content(array $post, array $exclude = []): array
    {
        $out = [];
        foreach ($post as $name => $value) {
            $name = (string) $name;
            if (in_array($name, self::TOKEN_FIELDS, true) || in_array($name, $exclude, true) || preg_match(self::SENSITIVE, $name)) {
                continue;
            }
            if (is_array($value)) {
                $value = self::content($value, $exclude);
            } elseif (is_scalar($value) || $value === null) {
                $value = $value === null ? '' : (string) $value;
            } else {
                continue;
            }
            $out[$name] = $value;
        }

        return $out;
    }
}
