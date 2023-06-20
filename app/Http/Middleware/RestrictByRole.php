<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use App\Models\Profile;

class RestrictByRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, $role, $guard = null): Response
    {
        $authGuard = Auth::guard($guard);

        if ($authGuard->guest()) {
            throw UnauthorizedException::notLoggedIn();
        }

        $roles = is_array($role)
            ? $role
            : explode('|', $role);

        $profile = Profile::where('user_id', $authGuard->user()->id)->first();

        // if (!$authGuard->user()->hasAnyRole($roles)) {
        //     throw UnauthorizedException::forRoles($roles);
        // }

        if (!$profile->hasAnyRole($roles)) {
            throw UnauthorizedException::forRoles($roles);
        }

        return $next($request);
    }
}
