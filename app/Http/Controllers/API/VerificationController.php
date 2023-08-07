<?php

namespace App\Http\Controllers\API;

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
    //
    use VerifiesEmails;

    public function __construct()
    {
        // $this->middleware('auth');
        $this->middleware('signed')->only('verify');
        $this->middleware('throttle:6,1')->only('verify', 'resend');
    }

    // Show the email verification notice.
    public function show(Request $request)
    {
        return $request->user()->hasVerifiedEmail()
            ? redirect($this->redirectPath())
            : view('auth.verify');
    }

    // Mark the authenticate user's email address as verified.
    public function verify(Request $request, User $user)
    {
        // $user = User::findOrFail($request->route('user'));

        if ($user->hasVerifiedEmail()) {
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/email/verify/verified');
        }

        if (!hash_equals((string) $request->route('hash'), sha1($user->getEmailForVerification()))) {
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/email/verify/invalid'); // Invalid Hash
        }

        if ($user->markEmailAsVerified()) {
            event(new Verified($request->user()));
            return redirect(env('FRONTEND_URL', 'http://localhost:5173') . '/email/verify/success'); // Successful verification
        }

        return redirect(env('FRONTEND_URL', 'http://localhost:5173'));

        if ($response = $this->verified($request)) {
            return $response;
        }

        return $request->wantsJson()
            ? new JsonResponse([], 204)
            : redirect($this->redirectPath())->with('verified', true);
    }

    public function resend(Request $request, User $user)
    {
        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'status' => 200,
                'message'   => 'Resend Email Verification',
                'result'    => []
            ]);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'status' => 201,
            'message'   => 'Resend Email Verification',
            'result'    => []
        ]);
    }
}
