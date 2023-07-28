<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictRequestEditor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {

        $request->validate([
            'role'              => ['required', 'in:service-provider,artists,organizer,customers',],
        ]);

        $user = $request->user();
        $role = $request->query('role');

        $profile = \App\Models\Profile::with('roles')->where('user_id', $user->id)->whereHas('roles', function ($query) use ($role) {
            $query->where('name', 'LIKE', '%' . $role . '%');
        })->first();

        if (!($profile->id === $request->route()->parameter('song')->creator_id)) {
            return $request->expectsJson()
                ? abort(403, 'Your do not own this..')
                : response()->json([
                    'status'    => 403,
                    'message'   => 'Your do not own this.',
                    'result'    => [
                        'auth'  => $request->user()->profiles(),
                        'song'  => $request->route()->parameter('song'),
                    ]
                ], 203);
        }
        return $next($request);
    }
}
