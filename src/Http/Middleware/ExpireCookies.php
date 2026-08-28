<?php

namespace Egarrido\NmsDevPanel\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class ExpireCookies
{
    public function handle(Request $request, Closure $next)
    {
        if (!$request->routeIs('nms-dev-panel.cookies.clear', 'nms-dev-panel.database.replace')) {
            return $next($request);
        }

        $names = $this->cookieNames($request);
        $response = $next($request);
        $responseCookies = $response->headers->getCookies();
        $names = array_merge($names, array_map(function ($cookie): string {
            return $cookie->getName();
        }, $responseCookies));

        $response->headers->set('Clear-Site-Data', '"cookies", "storage"');
        $this->expire($response, $request, $responseCookies, $names);

        return $response;
    }

    private function cookieNames(Request $request): array
    {
        $names = array_merge(
            array_keys($request->cookies->all()),
            config('nms-dev-panel.cookie_names', []),
            [$request->input('cookie_names', []), config('session.cookie')]
        );
        $names = $this->flatten($names);

        return array_values(array_filter(array_unique($names), function ($name): bool {
            return is_string($name) && preg_match('/^[!#$%&\'*+.^_`|~0-9A-Za-z-]+$/', $name) === 1;
        }));
    }

    private function expire($response, Request $request, array $cookies, array $names): void
    {
        $paths = array_merge((array) config('nms-dev-panel.cookie_paths', ['/']), (array) $request->input('cookie_paths', []));
        $paths = array_merge($paths, array_map(function ($cookie): string {
            return $cookie->getPath();
        }, $cookies));
        $domains = array_merge((array) config('nms-dev-panel.cookie_domains', [null]), $this->domainVariants($request->getHost()));

        foreach (array_unique($names) as $name) {
            foreach ($this->validPaths($paths) as $path) {
                foreach (array_unique($domains, SORT_REGULAR) as $domain) {
                    $response->headers->clearCookie($name, $path, $domain);
                }
            }
        }
    }

    private function domainVariants(string $host): array
    {
        if ($host === 'localhost' || filter_var($host, FILTER_VALIDATE_IP)) {
            return [null];
        }

        $parts = explode('.', $host);
        $domains = [null];

        while (count($parts) > 1) {
            $domains[] = implode('.', $parts);
            array_shift($parts);
        }

        return $domains;
    }

    private function validPaths(array $paths): array
    {
        return array_values(array_filter(array_unique($this->flatten($paths)), function ($path): bool {
            return is_string($path) && strpos($path, '/') === 0;
        }));
    }

    private function flatten(array $values): array
    {
        $flattened = [];
        array_walk_recursive($values, function ($value) use (&$flattened): void {
            $flattened[] = $value;
        });

        return $flattened;
    }
}
