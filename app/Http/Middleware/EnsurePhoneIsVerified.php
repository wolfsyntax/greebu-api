<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsurePhoneIsVerified
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle($request, Closure $next, $redirectToRoute = null)
    {
        if (!$request->user() || !$request->user()->phone_verified_at) {
            return $request->expectsJson()
                ? abort(403, 'Your phone number is not verified.')
                : response()->json([
                    'status' => 403,
                    'message' => 'Your phone number is not verified.',
                    'result' => []
                ]);
        }

        return $next($request);
    }
}
