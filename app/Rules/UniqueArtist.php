<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use App\Models\Profile;
use Illuminate\Support\Str;
use Throwable;

class UniqueArtist implements ValidationRule
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

            $profile = Profile::query();

            $profile->when(auth()->check(), function ($query) {
                return $query->whereNot('user_id', auth()->user()->id);
            });

            $profile = $profile->account('artists')->where(
                    'business_name',
                    Str::headline($value)
                )->first();

            $val = ucfirst(Str::headline($attribute));

            if ($profile) $fail("The $val has already been taken.");

        } catch (Throwable $e) {
            $fail('The :attribute is invalid.');
        }
    }
}
