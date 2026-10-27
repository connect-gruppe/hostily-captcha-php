<?php

declare(strict_types=1);

namespace Hostily\Captcha;

/**
 * The service could not be reached or did not answer usefully (network error, timeout,
 * HTTP 5xx, unreadable answer). The caller decides: let the request pass (fail open) or block it (fail closed).
 */
class UnavailableException extends \RuntimeException
{
}
