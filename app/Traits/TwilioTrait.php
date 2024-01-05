<?php

namespace App\Traits;

use Illuminate\Http\Request;
use Twilio\Rest\Client;
use Twilio\Exceptions\TwilioException;

trait TwilioTrait
{
    /**
     * @return array<string,mixed>
     */
    public function fetchDialingCode(string $country_code = 'PH')
    {
        $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));
        $country = $twilio->voice->v1->dialingPermissions->countries($country_code)->fetch();
        return $country->toArray();
    }

    /**
     * @return bool
     */
    public function sendMessage(string $recipient, string $message)
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

    /**
     * @return bool
     */
    public function sendOTP(string $recipient)
    {
        try {
            if ($recipient) {

                $client = new Client(config('services.twilio.sid'), config('services.twilio.auth_token'));
                $twilio = $client->verify->v2->services(env('TWILIO_SERVICE_ID'))
                    ->verifications->create($recipient, "sms");

                return $twilio->status === 'pending';
            }

            return false;
        } catch (TwilioException $th) {
            //throw $th;
            return false;
        }
    }

    /**
     * @return bool
     */
    public function verifyOTP(string $recipient, string $otp)
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
