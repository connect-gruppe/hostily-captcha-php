<?php

declare(strict_types=1);

namespace Hostily\Captcha\Tests;

use Hostily\Captcha\Client;
use Hostily\Captcha\UnavailableException;
use PHPUnit\Framework\TestCase;

/** Against a real service; runs only with HOSTILY_E2E_API, HOSTILY_E2E_SECRET, HOSTILY_E2E_TOKENS (3 tokens, space separated). */
final class E2eTest extends TestCase
{
    protected function setUp(): void
    {
        if (!getenv('HOSTILY_E2E_API')) {
            $this->markTestSkipped('no E2E service configured');
        }
    }

    public function testRealService(): void
    {
        $api = getenv('HOSTILY_E2E_API');
        $tokens = explode(' ', trim((string) getenv('HOSTILY_E2E_TOKENS')));
        $client = new Client((string) getenv('HOSTILY_E2E_SECRET'), $api, 10);

        $ok = $client->verifyForm(['hostily-captcha-token' => $tokens[0], 'name' => 'Ann', 'message' => 'Hello there', 'password' => 'hunter2'], '203.0.113.5');
        $this->assertTrue($ok->success, json_encode($ok));
        $this->assertSame([], $ok->gruende);
        $this->assertNotNull($ok->punkte);

        $again = $client->verify($tokens[0]);
        $this->assertFalse($again->success);
        $this->assertContains('token_bereits_verwendet', $again->gruende);

        $bad = (new Client('wrong-secret', $api, 10))->verify($tokens[1]);
        $this->assertFalse($bad->success);
        $this->assertTrue($bad->isConfigurationError());

        $this->expectException(UnavailableException::class);
        (new Client('x', 'http://127.0.0.1:1', 2))->verify('t');
    }
}
