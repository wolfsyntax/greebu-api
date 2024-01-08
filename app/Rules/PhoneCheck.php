<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Twilio\Exceptions\TwilioException;
use Twilio\Rest\Client;

class PhoneCheck implements ValidationRule
{
    /**
     * @var string
     */
    protected string $params;
    /**
     * @return void
     */
    public function __construct(string $param = 'PH')
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

            $lookup = $twilio->lookups->v2
                ->phoneNumbers($value)
                ->fetch(['countryCode' => $this->params]);

            // if (!$lookup->valid) $fail('The :attribute is must be in international standard format.');
            if (!$lookup->valid) $fail('Please provide a valid phone number.');
        } catch (TwilioException $e) {
            $fail('The :attribute is invalid.');
        }
    }
}
