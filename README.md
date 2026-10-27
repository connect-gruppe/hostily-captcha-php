# hostily/captcha (PHP)

**Deutsch**

Kleiner Client für [hostily Captcha](https://captcha.hostily.de): prüft auf dem Server das Token, das das Widget
`<hostily-captcha>` in das Formularfeld `hostily-captcha-token` (oder `g-recaptcha-response`) geschrieben hat.
PHP ≥ 7.4, nur `ext-curl` und `ext-json`, optional ein PSR-18-Client.

```sh
composer require hostily/captcha
```

```php
use Hostily\Captcha\Client;
use Hostily\Captcha\UnavailableException;

$captcha = new Client(getenv('HOSTILY_CAPTCHA_SECRET'));          // Secret nur auf dem Server!
try {
    $result = $captcha->verifyForm($_POST, $_SERVER['REMOTE_ADDR'] ?? null);
} catch (UnavailableException $e) {
    // Dienst nicht erreichbar / Zeitlimit: Sie entscheiden - blockieren oder durchlassen
}
if ($result->success) { /* annehmen */ } else { /* $result->gruende, $result->punkte */ }
```

* `verify($token, $remoteip, $formular)` prüft ein Token; mit `$formular` prüft der Dienst zusätzlich Honeypot, Mindestzeit,
  gleiche Einsendungen und Regeln. `verifyForm($_POST, …)` holt Token und Inhalt selbst aus dem Formular.
* `Form::token($_POST)` liefert nur das Token, `Form::content($_POST)` den Inhalt **ohne** Passwortfelder
  (`password`, `passwort`, `pass`, `pwd`, …), Secrets, CSRF-Tokens, Kartencodes und die Token-Felder. Passwörter verlassen den Server nie.
* `Result`: `success`, `gruende` (Kurzcodes, z. B. `token_bereits_verwendet`, `honeypot`), `punkte`, `errorCodes`, `hostname`,
  `isConfigurationError()` (Secret fehlt/falsch – ein Einrichtungsfehler, kein Spam-Urteil).
* Zeitlimit: dritter Parameter des Konstruktors (Sekunden, Vorgabe 5). Eigene Adresse: zweiter Parameter.
* Ohne Token wird der Dienst nicht gefragt (`success = false`, Grund `token_fehlt`). Jedes Token gilt nur für **eine** Prüfung.
* PSR-18: `new Client($secret, Client::DEFAULT_ENDPOINT, 5.0, new Psr18Transport($httpClient, $requestFactory, $streamFactory))`.
* Tests: `composer install && vendor/bin/phpunit`.

**English**

Small client for [hostily Captcha](https://captcha.hostily.de): verifies on the server the token that the
`<hostily-captcha>` widget put into the form field `hostily-captcha-token` (or `g-recaptcha-response`).
PHP ≥ 7.4, only `ext-curl` and `ext-json`, optional PSR-18 client.

Install with `composer require hostily/captcha`; use as in the example above.

* `verify($token, $remoteip, $formular)` verifies one token; with `$formular` the service also checks honeypot, minimum time,
  duplicate submissions and rules. `verifyForm($_POST, …)` takes token and content from the form itself.
* `Form::token()` returns only the token; `Form::content()` returns the content **without** password fields, secrets, CSRF
  tokens, card codes and the token fields. Passwords never leave your server.
* `Result`: `success`, `gruende` (short codes), `punkte` (score), `errorCodes`, `hostname`, `isConfigurationError()`
  (missing/wrong secret - a setup error, not a spam verdict).
* Timeout: third constructor argument (seconds, default 5). Custom address: second argument.
* `UnavailableException` means "service not reachable / timeout / HTTP 5xx / 429": you decide whether to block or let the request pass.
* No token → the service is not called (`success = false`, reason `token_fehlt`). Each token is valid for **one** check only.
* PSR-18: pass a `Psr18Transport` as the fourth argument. Tests: `composer install && vendor/bin/phpunit`.

MIT License.
