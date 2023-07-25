<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules;
use App\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Password;
use Illuminate\Foundation\Auth\ResetsPasswords;
use Illuminate\Support\MessageBag;
use DB;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Support\Str;

class ResetPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset requests
    | and uses a simple trait to include this behavior. You're free to
    | explore this trait and override any methods you wish to tweak.
    |
    */

    use ResetsPasswords;

    /**
     * Where to redirect users after resetting their password.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Reset the given user's password.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function reset(Request $request)
    {
        $request->validate([
            'token' => 'required',
            'email' => 'required|email|exists:users',
            'password' => !app()->isProduction() ? ['required', 'confirmed',] : [
                'required', 'confirmed', Rules\Password::defaults(), Rules\Password::min(8)->mixedCase()
                    ->letters()
                    ->numbers()
                    ->symbols()
                    ->uncompromised()
            ],
        ]);

        $error = new MessageBag;

        $passReset = DB::table('password_resets')->where([
            'email' => $request->input('email'),
            'token' => $request->input('token'),
        ])->first();

        if ($passReset) {

            $user = User::where('email', $request->input('email'))->first();

            $user->update([
                'password'       => $request->input('password'),
            ]);

            return response()->json([
                'status'    => 200,
                'message'   => 'Password successfully changed.',
                'result'    => [
                    'user'  => $user,
                ],
            ]);
        } else {
            if (!User::where('email', $request->input('email'))->exists()) $error->add('email', 'Email is invalid.');
            else $error->add('token', 'Token is invalid or not matched.');
        }

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user, string $password) {
                $user->forceFill([
                    'password' => $password
                ])->setRememberToken(Str::random(60));

                $user->save();

                event(new PasswordReset($user));
            }
        );

        return response()->json([
            'status'        => 422,
            'message'       => 'Unprocessible Entity.',
            'result'        => [
                'errors'    => $error->getMessages(),
                'status'    => $status,
            ],
        ], 203);
    }
}
