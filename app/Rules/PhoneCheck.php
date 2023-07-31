<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class PhoneCheck implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        try {
            $twilio = new Client(env('TWILIO_SID'), env('TWILIO_AUTH_TOKEN'));

            $lookup = $twilio->lookups->v2
                ->phoneNumbers($value)
                ->fetch();

            if (!$lookup->valid) $fail('The :attribute is must be in international standard format.');
        } catch (TwilioException $e) {
            $fail('The :attribute is invalid.');
        }
    }
}
