<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use App\Models\User;

class UniquePhone implements ValidationRule
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

            $user = User::whereNotIn('phone', ['+639955189602', '+639184592272',])->where('phone', $value)->first();

            if ($user) $fail("The :attribute has already been taken.");
        } catch (\Throwable $e) {
            $fail('The :attribute is invalid.');
        }
    }
}
