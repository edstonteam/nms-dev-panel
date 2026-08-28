<?php

namespace Egarrido\NmsDevPanel\Tests\Unit;

use Egarrido\NmsDevPanel\Http\Middleware\ExpireCookies;
use Egarrido\NmsDevPanel\Tests\TestCase;
use Illuminate\Http\Request;
use Illuminate\Routing\Route;
use Symfony\Component\HttpFoundation\Cookie;

class ClearCookiesControllerTest extends TestCase
{
    public function test_it_expires_request_response_and_frontend_cookies_after_the_response(): void
    {
        $request = Request::create('https://app.local.test/_nms-dev-panel/cookies', 'POST', [
            'cookie_names' => ['frontend_cookie'],
            'cookie_paths' => ['/admin'],
        ], [
            'session' => 'secret',
            'http_only_cookie' => 'hidden',
        ]);
        $request->setRouteResolver(function (): Route {
            return (new Route('POST', '/_nms-dev-panel/cookies', function (): void {
            }))->name('nms-dev-panel.cookies.clear');
        });

        $response = (new ExpireCookies())->handle($request, function () {
            $response = response()->json(['cleared' => true]);
            $response->headers->setCookie(new Cookie('late_session', 'renewed'));

            return $response;
        });

        $this->assertSame('"cookies", "storage"', $response->headers->get('Clear-Site-Data'));
        $names = array_unique(array_map(function (Cookie $cookie): string {
            $this->assertTrue($cookie->isCleared());

            return $cookie->getName();
        }, $response->headers->getCookies()));

        $this->assertEmpty(array_diff([
            'session',
            'http_only_cookie',
            'frontend_cookie',
            'late_session',
            'XSRF-TOKEN',
        ], $names));
    }

    public function test_it_clears_cookies_on_the_database_replacement_response(): void
    {
        $request = Request::create('/_nms-dev-panel/database', 'POST', [], ['session' => 'secret']);
        $request->setRouteResolver(function (): Route {
            return (new Route('POST', '/_nms-dev-panel/database', function (): void {
            }))->name('nms-dev-panel.database.replace');
        });

        $response = (new ExpireCookies())->handle($request, function () {
            return response()->json(['replaced' => true]);
        });

        $this->assertSame('"cookies", "storage"', $response->headers->get('Clear-Site-Data'));
        $this->assertTrue($response->headers->getCookies()[0]->isCleared());
    }
}
