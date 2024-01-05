<?php

use Illuminate\Support\Facades\Broadcast;
use App\Models\Profile;
use App\Models\User;

/*
|--------------------------------------------------------------------------
| Broadcast Channels
|--------------------------------------------------------------------------
|
| Here you may register all of the event broadcasting channels that your
| application supports. The given channel authorization callbacks are
| used to check if an authenticated user can listen to the channel.
|
*/
use Illuminate\Support\Facades\Auth;

Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return $user->id === $id;
});

Broadcast::channel('profile.{id}', function ($user, $id) {
    $profile = Profile::find($id);

    if (!$profile) return false;

    return $user->id === $profile->user_id;
});

Broadcast::channel('user-info.{id}', function ($user, $id) {

    $data = User::find($id);

    if (!$data) return false;

    return $user->id === $data->id;
});

Broadcast::channel('sync-data', function ($user) {
    return Auth::check();
});
