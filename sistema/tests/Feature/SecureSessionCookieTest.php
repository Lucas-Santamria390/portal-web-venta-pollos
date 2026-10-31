<?php

namespace Tests\Feature;

use Illuminate\Testing\TestResponse;
use Symfony\Component\HttpFoundation\Cookie;
use Tests\TestCase;

class SecureSessionCookieTest extends TestCase
{
    public function test_the_session_cookie_is_secure_when_the_request_arrives_over_https(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '127.0.0.1',
            'HTTP_X_FORWARDED_PROTO' => 'https',
        ])->get('/login');

        $cookie = $this->sessionCookie($response);

        $this->assertNotNull($cookie);
        $this->assertTrue($cookie->isSecure());
    }

    public function test_the_session_cookie_stays_usable_over_plain_http_in_the_local_network(): void
    {
        $response = $this->withServerVariables([
            'REMOTE_ADDR' => '192.168.50.10',
        ])->get('/login');

        $cookie = $this->sessionCookie($response);

        $this->assertNotNull($cookie);
        $this->assertFalse($cookie->isSecure());
    }

    private function sessionCookie(TestResponse $response): ?Cookie
    {
        $name = config('session.cookie');

        foreach ($response->headers->getCookies() as $cookie) {
            if ($cookie->getName() === $name) {
                return $cookie;
            }
        }

        return null;
    }
}
