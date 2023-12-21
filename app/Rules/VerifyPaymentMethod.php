<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use Luigel\Paymongo\Facades\Paymongo;

class VerifyPaymentMethod implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        //
        try {

            $paymentMethod = Paymongo::paymentMethod()->find($value);

            if (!$paymentMethod) $fail('The :attribute is invalid.')

        } catch (Throwable $e) {
            $fail('The :attribute is invalid.');
        }
    }
}
