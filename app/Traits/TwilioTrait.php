<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

trait TwilioTrait
{

    public function fetchDialingCode($country_code = 'PH') {
        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $country = $twilio->voice->v1->dialingPermissions->countries($country_code)->fetch();
        return $country->toArray();
    }

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

            $client = new Client(config('services.twilio.sid'), config('services.twilio.auth_token'));
            $twilio = $client->verify->v2->services(env('TWILIO_SERVICE_ID'))
                ->verifications->create($recipient, "sms");

            return $twilio->status === 'pending';
        } catch (TwilioException $th) {
            //throw $th;
            return false;
        }
    }

    public function verifyOTP($recipient, $otp)
    {
        try {

            //code...
            $client = new Client(config('services.twilio.sid'), config('services.twilio.auth_token'));
            $twilio = $client->verify->v2->services(config('services.twilio.service_id'))
                ->verificationChecks
                ->create([
                    'to'    => $recipient,
                    'code'  => $otp,
                ]);

            return $twilio->status === 'approved';
            // return true;
        } catch (TwilioException $th) {
            return false;
        }
    }
}
