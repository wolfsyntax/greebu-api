<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

trait TwilioTrait
{

    public function sendMessage($recipient, $message)
    {
        try {

            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

            $twilio->messages->create(
                $recipient,
                ['from' => env('TWILIO_NUMBER'), 'body' => $message]
            );

            return true;
        } catch (TwilioException $e) {
            return false;
        }
    }

    public function sendOTP($recipient)
    {
        try {
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $twilio->verify->v2->services(env('TWILIO_SERVICE_ID'))
                ->verifications->create($recipient, "sms");

            return true;
        } catch (TwilioException $th) {
            //throw $th;
            return false;
        }
    }

    public function verifyOTP($recipient, $otp)
    {
        try {
            //code...
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
            $twilio->verify->v2->services(env('TWILIO_SERVICE_ID'))
                ->verificationChecks
                ->create([
                    'to'    => $recipient,
                    'code'  => $otp,
                ]);
            return true;
        } catch (TwilioException $th) {
            return false;
        }
    }
}
