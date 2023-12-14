<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Exceptions\UnauthorizedException;
use App\Models\Profile;
use App\Models\Artist;

class RestrictSongRequestor
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $role = $request->route()->parameter('role');

        $role = is_array($role) ? '' : $role;

        if (!$role) {
            return $request->expectsJson() ? abort(404)
                : response()->json([
                    'status'    => 404,
                    'message'   => 'Page not found.',
                    'result'    => [

                    ]
                ], 203);
        }

        $artistProfile = Profile::myAccount('artists')->first();

        // Get artists profile id
        $artists = Artist::select('profile_id')
            // ->when($artistProfile, function($query) use($artistProfile) {
            //     return $query->where('profile_id', '!=', $artistProfile->id);
            // })
            ->whereIn('id', collect($request->artists)->pluck('id'))->get();

        //
        $profile = Profile::myAccount('artists')->whereIn('id', $artists)->first();

        if ($profile) {
            return $request->expectsJson() ? abort(403, 'You cannot request from your own profile.')
                : response()->json([
                    'status'    => 403,
                    'message'   => 'You cannot request from your own profile.',
                    'result'    => [

                    ]
                ], 203);
        }

        return $next($request);
    }
}
