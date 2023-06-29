<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use App\Models\Artist;
use App\Models\ArtistType;
use App\Models\Plan;

class SubscriptionController extends Controller
{
    //

    public function pricings(Request $request, $plan = 'artists')
    {
        $customer = Plan::where('account_type', 'customers')->first();
        $others = Plan::with('inclusions')->where('account_type', $plan)->orderBy('plan_type')->get();

        return response()->json([
            'status'    => 200,
            'message'   => "Successfully fetched plans and inclusions",
            'result'    => [
                '_plan' => $customer,
                'plans' => $others,
            ],
        ], 200);
    }

    public function upgradeAccount(User $user)
    {
        $profile = Profile::where('user_id', $user->id)->first();

        if (!$profile->hasRole('artists')) {



            Artist::create([
                'profile_id' => $profile->id,
                'artist_type_id' => ArtistType::first()->id,
            ]);

            $profile->assignRole('artists');

            return response()->json([
                'message' => 'Artists',
            ]);
        }

        return response()->json([
            'message' => 'Has roles',
        ]);
    }
}
