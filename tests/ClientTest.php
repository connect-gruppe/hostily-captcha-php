<?php

declare(strict_types=1);

namespace Hostily\Captcha\Tests;

use Hostily\Captcha\Client;
use Hostily\Captcha\Form;
use Hostily\Captcha\UnavailableException;
use PHPUnit\Framework\TestCase;

final class ClientTest extends TestCase
{
    public function testSuccess(): void
    {
        $t = new FakeTransport();
        $r = (new Client('s3cret', 'https://x.example/', 2.5, $t))->verify(' tok ', '1.2.3.4', ['name' => 'Ann']);
        $this->assertTrue($r->success);
        $this->assertSame(0, $r->punkte);
        $this->assertSame('example.de', $r->hostname);
        $this->assertSame('https://x.example/api/v1/pruefen', $t->calls[0]['url']);
        $this->assertSame(2.5, $t->calls[0]['timeout']);
        $this->assertSame(['secret' => 's3cret', 'response' => 'tok', 'remoteip' => '1.2.3.4', 'formular' => ['name' => 'Ann']], $t->calls[0]['json']);
    }

    public function testRejected(): void
    {
        $t = new FakeTransport();
        $t->body = '{"success":false,"error-codes":["timeout-or-duplicate"],"gruende":["token_bereits_verwendet"]}';
        $r = (new Client('s', 'https://x', 5, $t))->verify('tok');
        $this->assertFalse($r->success);
        $this->assertSame(['token_bereits_verwendet'], $r->gruende);
        $this->assertFalse($r->isConfigurationError());
    }

    public function testWrongSecretIsConfigurationError(): void
    {
        $t = new FakeTransport();
        $t->body = '{"success":false,"error-codes":["invalid-input-secret"],"gruende":["secret_ungueltig"]}';
        $r = (new Client('bad', 'https://x', 5, $t))->verify('tok');
        $this->assertFalse($r->success);
        $this->assertTrue($r->isConfigurationError());
    }

    public function testMissingTokenDoesNotCallService(): void
    {
        $t = new FakeTransport();
        $r = (new Client('s', 'https://x', 5, $t))->verify(null);
        $this->assertFalse($r->success);
        $this->assertSame(['token_fehlt'], $r->gruende);
        $this->assertCount(0, $t->calls);
    }

    public function testNetworkErrorThrows(): void
    {
        $t = new FakeTransport();
        $t->fail = true;
        $this->expectException(UnavailableException::class);
        (new Client('s', 'https://x', 5, $t))->verify('tok');
    }

    /** @dataProvider badAnswers */
    public function testUnusableAnswerThrows(int $status, string $body): void
    {
        $t = new FakeTransport();
        $t->status = $status;
        $t->body = $body;
        $this->expectException(UnavailableException::class);
        (new Client('s', 'https://x', 5, $t))->verify('tok');
    }

    public function badAnswers(): array
    {
        return [[502, 'Bad Gateway'], [200, 'not json'], [200, '{"foo":1}'], [429, '{"success":false}']];
    }

    public function testTokenFields(): void
    {
        $this->assertSame('a', Form::token(['hostily-captcha-token' => 'a', 'g-recaptcha-response' => 'b']));
        $this->assertSame('b', Form::token(['g-recaptcha-response' => 'b']));
        $this->assertNull(Form::token(['x' => 1]));
        $this->assertNull(Form::token(['hostily-captcha-token' => '  ']));
    }

    public function testContentDropsPasswordsAndTokens(): void
    {
        $post = [
            'name' => 'Ann', 'message' => 'Hi', 'password' => 'x', 'Passwort' => 'x', 'pass' => 'x', 'user_pass' => 'x',
            'pwd' => 'x', 'password_confirmation' => 'x', '_csrf' => 'x', 'csrf_token' => 'x', 'api_secret' => 'x',
            'hostily-captcha-token' => 't', 'g-recaptcha-response' => 't', 'passenger' => 'Bob',
            'nested' => ['ok' => 'v', 'new_password' => 'x'], 'n' => 5, 'nothing' => null,
        ];
        $c = Form::content($post);
        $this->assertSame(['name' => 'Ann', 'message' => 'Hi', 'passenger' => 'Bob', 'nested' => ['ok' => 'v'], 'n' => '5', 'nothing' => ''], $c);
    }

    public function testVerifyFormNeverSendsPassword(): void
    {
        $t = new FakeTransport();
        (new Client('s', 'https://x', 5, $t))->verifyForm(['hostily-captcha-token' => 'tok', 'email' => 'a@b.de', 'password' => 'hunter2'], '9.9.9.9');
        $this->assertStringNotContainsString('hunter2', $t->calls[0]['raw']);
        $this->assertSame(['email' => 'a@b.de'], $t->calls[0]['json']['formular']);
    }

    public function testVerifyFormWithoutContent(): void
    {
        $t = new FakeTransport();
        (new Client('s', 'https://x', 5, $t))->verifyForm(['g-recaptcha-response' => 'tok', 'a' => 'b'], null, false);
        $this->assertArrayNotHasKey('formular', $t->calls[0]['json']);
    }
}
