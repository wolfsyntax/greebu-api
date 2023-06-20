<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Profile;
use App\Models\Artist;
use App\Models\ArtistType;

class SubscriptionController extends Controller
{
    //
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
