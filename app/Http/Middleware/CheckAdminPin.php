<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAdminPin
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (session('admin_pin_authenticated') === true) {
            return $next($request);
        }

        if (app()->environment('testing') && ! config('kassa.enforce_pin_in_tests', false)) {
            return $next($request);
        }

        // Store intended URL if not already on the pin login page
        if (! $request->routeIs('admin.pin') && ! $request->routeIs('admin.pin.submit')) {
            session(['admin_intended_url' => $request->fullUrl()]);
        }

        return redirect()->route('admin.pin');
    }
}
