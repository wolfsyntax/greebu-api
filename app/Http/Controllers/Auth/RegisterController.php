<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;

use Illuminate\Foundation\Auth\RegistersUsers;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Auth\Events\Registered;

use App\Models\User;
use App\Models\Profile;

class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */

    // use RegistersUsers;

    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    /**
     * Get a validator for an incoming registration request.
     *
     * @param  array  $data
     * @return \Illuminate\Contracts\Validation\Validator
     */
    protected function validator(array $data)
    {
        return Validator::make($data, [
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'username' => ['required', 'string',  'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);
    }

    public function showRegistrationForm()
    {
    }

    public function register(Request $request)
    {

        $email_rules = ['required', 'string', 'email', 'max:255', 'unique:users'];

        if ($request->input('reg_type') === 'phone') {
            $email_rules = ['required', 'string', 'regex:/^((\+63)|0)[.\- ]?9[0-9]{2}[.\- ]?[0-9]{3}[.\- ]?[0-9]{4}$/i'];
        }

        $validator = Validator::make($request->all(), [
            'first_name'    => ['required', 'string', 'max:255'],
            'last_name'     => ['required', 'string', 'max:255'],
            'email'         => $email_rules,
            'username'      => ['required', 'string',  'max:255', 'unique:users'],
            'password'      => ['required', 'string', 'min:8', 'confirmed'],
            'account_type'  => ['string', Rule::in(['customers', 'artists', 'organizer', 'service-provider']),],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'    => 422,
                'message'   => 'Unprocessible Entity',
                'results'   => [
                    'errors' => $validator->errors(),
                ],
            ], 203);
        }

        $formData = [
            'first_name'    => $request->first_name,
            'last_name'     => $request->last_name,
            'username'      => $request->username,
            'email'         => $request->email,
            'password'      => $request->password,
        ];

        if ($request->input('reg_type') === 'phone') {

            $formData = [
                'first_name'    => $request->first_name,
                'last_name'     => $request->last_name,
                'username'      => $request->username,
                'phone'         => $request->phone,
                'password'      => $request->password,
            ];
        }

        $user = User::create($formData);

        $profile = Profile::create([
            'user_id' => $user->id,
            'business_email'    => $user->email,
            'phone'             => $user->phone,
            'business_name'     => $user->fullname,
            'city'              => 'Naga City',
            'zip_code'          => '4400',
            'province'          => 'Camarines Sur',
        ])->assignRole($request->input('account_type', 'customers'));
        $artist_profile = "";
        if ($request->input('account_type', 'customers') === 'artists') {

            $artistType = \App\Models\ArtistType::first();
            $genre = \App\Models\Genre::where('title', 'Others')->first();

            $artist_profile = \App\Models\Artist::create([
                'profile_id'        => $profile->id,
                'artist_type_id'    => $artistType->id,
            ]);

            $languages = \App\Models\SupportedLanguage::get();
            $artist_profile->genres()->sync($genre);
            $artist_profile->languages()->sync($languages);
        }

        event(new Registered($user));

        return response()->json([
            'message' => 'Account successfully registered.',
        ], 201);

        return redirect()->to('/login');
    }
}
