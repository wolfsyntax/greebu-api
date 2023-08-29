<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
//
use App\Models\Profile;
use App\Models\Artist;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Lang;
use App\Libraries\AwsService;

trait SongTrait
{
    public function audioUpload(Request $request)
    {

        $profile = Profile::with('roles')->where('user_id', auth()->user()->id)->whereHas('roles', function ($query) {
            $query->where('name', 'artists');
        })->first();

        if ($profile) {
            $artist = Artist::where('profile_id', $profile->id)->first();

            if ($artist) {

                $artist->song_title = $request->input('song_title');

                $service = new AwsService();

                if ($request->hasFile('song')) {
                    if ($artist->song && !filter_var($artist->song, FILTER_VALIDATE_URL)) {
                        $service->delete_aws_object($artist->song);
                    }

                    $artist->song = $service->put_object_to_aws('artist_songs/audio_' . uniqid() . '.' . $request->file('song')->getClientOriginalExtension(), $request->file('song'));
                }

                $artist->save();
            }

            return false;
        }

        return false;
    }
}
