<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Models\User;
use App\Traits\UserTrait;

use DB;

use App\Http\Controllers\Controller;
use App\Notifications\ForgotPass;
// use App\Mail\ResetPassword;

class ForgotPasswordController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Password Reset Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling password reset emails and
    | includes a trait which assists in sending these notifications from
    | your application to your users. Feel free to explore this trait.
    |
    */

    // use SendsPasswordResetEmails;
    use UserTrait;
    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => !app()->isProduction() ? ['required', 'string', 'email', 'max:255',] : ['required', 'string', 'email:rfc,dns', 'max:255',],
        ]);

        $user = User::select('first_name', 'email')->where('email', $request->input('email'))->first();

        if ($user) {
            $token = Str::random(64);

            DB::table('password_resets')->insert([
                'email' => $request->input('email'),
                'token' => $token,
                'created_at' => now()
            ]);


            $user->notify(new ForgotPass($token, $user));

            // $status = Password::sendResetLink($request->only('email'));

            return response()->json([
                'status'        => 200,
                'message'       => 'Forgot password.',
                'result'        => [
                    'token'     => $token,
                    'user'         => $user,
                    // $request->input('email')
                    'mask'      => Str::of($request->input('email'))->mask('*', 3, -5)
                ],
            ]);
        } else {
            return response()->json([
                'status'        => 200,
                'message'       => 'Email not registered.',
                'result'        => [],
            ]);
        }
    }
}
