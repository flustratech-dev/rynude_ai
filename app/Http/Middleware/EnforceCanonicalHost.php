<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Keep the whole app on ONE local host so the session cookie isn't split.
 *
 * `php artisan serve` answers on both `localhost` and `127.0.0.1` (they resolve
 * to the same server), but the browser treats them as two different sites and
 * scopes cookies per-host. Logging in on one and then loading/refreshing on the
 * other silently drops the auth cookie — the "logout on every refresh" bug, and
 * the reason `/api/*` calls bounce to the login page (so chat shows no answer).
 *
 * We pick the canonical host from APP_URL and 302-redirect the other known local
 * aliases to it, so the auth cookie always lives on a single host. This only
 * ever touches local aliases (localhost / 127.0.0.1 / 0.0.0.0 / ::1) and only
 * when APP_URL itself points at one of them, so real domains are never affected.
 */
class EnforceCanonicalHost
{
    /**
     * Hostnames that all reach the same local dev server but are separate cookie
     * jars in the browser.
     */
    private const LOCAL_ALIASES = ['localhost', '127.0.0.1', '0.0.0.0', '::1', '[::1]'];

    public function handle(Request $request, Closure $next): Response
    {
        $canonical = parse_url((string) config('app.url'), PHP_URL_HOST);

        // Only enforce when the canonical host is itself a local alias. This makes
        // the middleware a no-op in production (real domain in APP_URL).
        if (! $canonical || ! in_array($canonical, self::LOCAL_ALIASES, true)) {
            return $next($request);
        }

        $current = $request->getHost();

        // Redirect only top-level GET navigations between known local aliases.
        // POST/PUT and JSON/XHR calls are left alone: a redirect would drop the
        // body, and once the page itself is on the canonical host every relative
        // same-origin request already stays there.
        if ($request->isMethod('GET')
            && ! $request->expectsJson()
            && ! $request->ajax()
            && $current !== $canonical
            && in_array($current, self::LOCAL_ALIASES, true)) {
            $port = $request->getPort();
            $hostWithPort = $canonical . (in_array($port, [null, 80, 443], true) ? '' : ':' . $port);
            $target = $request->getScheme() . '://' . $hostWithPort . $request->getRequestUri();

            return redirect()->to($target, 302);
        }

        return $next($request);
    }
}
