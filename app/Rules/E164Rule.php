<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class E164Rule implements ValidationRule
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
            //code...
            if (startsWith($value, '+')) {
                $fail('The :attribute field must be start with + symbol.');
            }
            if (!preg_match('/^\+[1-9]\d{1,14}$/i', $value)) {
                $fail('The :attribute field must be in international phone number format.');
            }
        } catch (\Throwable $th) {
            //throw $th;
            $fail('The :attribute is invalid phone number.');
        }
    }
}
