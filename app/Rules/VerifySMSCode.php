<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Traits\TwilioTrait;

class VerifySMSCode implements ValidationRule
{
    use TwilioTrait;

    protected string $phone;

    public function __construct(string $phone)
    {
        $this->phone = $phone;
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
        try {
            if (!$this->phone) $fail("The :attribute required a phone number.");
            if (!$this->verifyOTP($this->phone, $value)) $fail("The :attribute is invalid code.");
        } catch (\Throwable $e) {
            $fail('The :attribute is invalid.');
        }
    }
}
