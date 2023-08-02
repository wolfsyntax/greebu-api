<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class PhoneCheck implements ValidationRule
{
    protected $params;

    public function __construct($param = 'PH')
    {
        $this->params = $param;
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        try {
            $twilio = new Client(config('services.twilio.sid'), config('services.twilio.auth_token'));

            $lookup = $twilio->lookups->v1
                ->phoneNumbers($value)
                ->fetch(['countryCode' => $this->params]);

            if (!$lookup->valid) $fail('The :attribute is must be in international standard format.');
        } catch (TwilioException $e) {
            $fail('The :attribute is invalid.');
        }
    }
}
