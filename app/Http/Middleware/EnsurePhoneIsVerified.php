<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\User;
use App\Models\Profile;

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

            $user = User::find($request->user()->id);
            $profile = Profile::where('user_id', $user->id)->first();

            return $request->expectsJson()
                ? abort(403, 'Your phone number is not verified.')
                : response()->json([
                    'status'        => 403,
                    'message'       => 'Your phone number is not verified.',
                    'result'        => [
                        'user'      => $user,
                        'profile'   => $profile,
                    ],
                ]);
        }

        return $next($request);
    }
}
