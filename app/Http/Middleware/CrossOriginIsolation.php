<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Enables cross-origin isolation so the page can use SharedArrayBuffer,
 * which the in-browser code runner needs for a blocking, interactive
 * stdin (real input()). Apply only to pages that host the runner.
 *
 * COEP "credentialless" is used (instead of "require-corp") so we can
 * still load cross-origin assets (Pyodide/xterm CDNs) without each one
 * having to send CORP headers. Supported by Chromium (incl. Safe Exam
 * Browser) and Firefox.
 */
class CrossOriginIsolation
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        $response->headers->set('Cross-Origin-Opener-Policy', 'same-origin');
        $response->headers->set('Cross-Origin-Embedder-Policy', 'credentialless');

        return $response;
    }
}
