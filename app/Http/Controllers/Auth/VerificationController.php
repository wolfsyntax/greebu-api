<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use Illuminate\Foundation\Auth\VerifiesEmails;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Models\User;

class VerificationController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Email Verification Controller
    |--------------------------------------------------------------------------
    |
    | This controller is responsible for handling email verification for any
    | user that recently registered with the application. Emails may also
    | be re-sent if the user didn't receive the original email message.
    |
    */

    // use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    // protected $redirectTo = RouteServiceProvider::HOME;



    /**
     * Create a new controller instance.
     *
     * @return void
     */
    // public function __construct()
    // {
    //     $this->middleware('auth');
    //     $this->middleware('signed')->only('verify');
    //     $this->middleware('throttle:6,1')->only('verify', 'resend');
    // }

    // public function show(Request $request)
    // {
    //     return $request->user()->hasVerifiedEmail()
    //         ? redirect($this->redirectPath())
    //         : view('auth.verify');
    // }

    // public function verify(Request $request)
    // {
    //     if (!hash_equals((string) $request->route('id'), (string) $request->user()->getKey())) {
    //         throw new AuthorizationException;
    //     }

    //     if (!hash_equals((string) $request->route('hash'), sha1($request->user()->getEmailForVerification()))) {
    //         throw new AuthorizationException;
    //     }

    //     if ($request->user()->hasVerifiedEmail()) {
    //         return $request->wantsJson()
    //             ? new JsonResponse([], 204)
    //             : redirect($this->redirectPath());
    //     }

    //     if ($request->user()->markEmailAsVerified()) {
    //         event(new Verified($request->user()));
    //     }

    //     if ($response = $this->verified($request)) {
    //         return $response;
    //     }

    //     return $request->wantsJson()
    //         ? new JsonResponse([], 204)
    //         : redirect($this->redirectPath())->with('verified', true);
    // }

    // public function resend(Request $request)
    // {
    //     if ($request->user()->hasVerifiedEmail()) {
    //         return $request->wantsJson()
    //             ? new JsonResponse([], 204)
    //             : redirect($this->redirectPath());
    //     }

    //     $request->user()->sendEmailVerificationNotification();

    //     return $request->wantsJson()
    //         ? new JsonResponse([], 202)
    //         : back()->with('resent', true);
    // }

    use VerifiesEmails;

    /**
     * Where to redirect users after verification.
     *
     * @var string
     */
    protected $redirectTo = RouteServiceProvider::HOME;

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        // $this->middleware('auth');
        // $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    public function verify(Request $request, User $user, $hash)
    {
        // if (!hash_equals((string) $request->route('id'), (string) $request->user()->getKey())) {
        //     throw new AuthorizationException;
        // }

        // if (!hash_equals((string) $request->route('hash'), sha1($request->user()->getEmailForVerification()))) {
        //     throw new AuthorizationException;
        // }

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            throw new AuthorizationException;
        }

        if ($user->hasVerifiedEmail()) {
            return $request->wantsJson()
                ? new JsonResponse([], 204)
                : redirect($this->redirectPath());
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        if ($response = $this->verified($request)) {
            return $response;
        }

        if ($request->wantsJson()) {
            return new JsonResponse([], 204);
        } else {
            if ($user->phone_verified_at) {
                return redirect(env('FRONTEND_URL'))->with('verified', true);
            } else {
                return redirect(env('FRONTEND_URL') . '/register?id=' . $user->id)->with('verified', true);
            }
        }
        // return $request->wantsJson()
        //     ? new JsonResponse([], 204)
        //     : redirect(env('FRONTEND_URL'))->with('verified', true);
    }
}
