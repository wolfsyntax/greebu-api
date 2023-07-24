<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Password;
use App\Models\User;

use DB;

use App\Http\Controllers\Controller;
// use Illuminate\Foundation\Auth\SendsPasswordResetEmails;
use App\Mail\ResetPassword;

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

    /**
     * Send a reset link to the given user.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Http\JsonResponse
     */
    public function sendResetLinkEmail(Request $request)
    {
        $request->validate([
            'email' => !app()->isProduction() ? ['required', 'string', 'email', 'max:255', 'exists:users,email',] : ['required', 'string', 'email:rfc,dns', 'max:255', 'exists:users,email',],
        ]);

        $token = Str::random(64);

        DB::table('password_resets')->insert([
            'email' => $request->input('email'),
            'token' => $token,
            'created_at' => now()
        ]);

        $user = User::select('first_name')->where('email', $request->input('email'))->first();
        $mail = Mail::send('email.reset_link', ['token' => $token, 'first_name' => $user->first_name], function ($message) use ($request) {
            $message->to($request->input('email'));
            $message->subject('Reset Password');
        });

        return response()->json([
            'status'    => 200,
            'message'   => 'Forgot password',
            'result'    => [
                'token' => $token,
                'mail'  => $mail,
            ],
        ]);
        // return $response == Password::RESET_LINK_SENT
        //     ? $this->sendResetLinkResponse($request, $response)
        //     : $this->sendResetLinkFailedResponse($request, $response);
    }
}
