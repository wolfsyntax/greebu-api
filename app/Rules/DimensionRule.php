<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Traits\Conditionable;

class DimensionRule implements ValidationRule
{

    /**
     * The constraints for the dimensions rule.
     *
     * @var array<mixed>
     */
    protected $constraints = [];

    /**
     * Create a new dimensions rule instance.
     *
     * @param  array  $constraints
     * @return void
     */
    public function __construct(array $constraints = [])
    {
        $this->constraints = $constraints;
    }

    /**
     * Convert the rule to a validation string.
     *
     * @return string
     */
    public function __toString(string $category = 'default')
    {

        if ($this->constraints['width'] > 0 && $this->constraints['height'] > 0 && $category === 'default') {
            return 'dimensions:width=' . $this->constraints['width'] . ',height=' . $this->constraints['height'];
        }

        if ($this->constraints['max_width'] > 0 && $this->constraints['max_height'] > 0 && $category === 'max') {
            return 'dimensions:max_width=' . $this->constraints['max_width'] . ',max_height=' . $this->constraints['max_height'];
        }

        if ($this->constraints['min_width'] > 0 && $this->constraints['min_height'] > 0 && $category === 'min') {
            return 'dimensions:min_width=' . $this->constraints['min_width'] . ',min_height=' . $this->constraints['min_height'];
        }

        if ($this->constraints['ratio'] && $category === 'ratio') {
            return 'dimensions:ratio' . $this->constraints['ratio'];
        }

        return '';
    }
    /**
     * Run the validation rule.
     *
     * @param  \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {

        $message = $this->__toString('min');

        if ($message) {
            $min = Validator::make([
                $attribute => $value
            ], [
                $attribute => $message,
            ]);

            if ($min->fails()) $fail(':Attribute minimum dimension cannot be less than ' . $this->constraints['min_width'] . 'px x ' . $this->constraints['min_height'] . 'px.');
        }

        $message = $this->__toString('max');

        if ($message) {
            $max = Validator::make([
                $attribute => $value
            ], [
                $attribute => $message,
            ]);

            if ($max->fails()) $fail(':Attribute maximum dimension cannot be greater than ' . $this->constraints['max_width'] . 'px x ' . $this->constraints['max_height'] . 'px.');
        }

        $message = $this->__toString('default');

        if ($message) {
            $default = Validator::make([
                $attribute => $value
            ], [
                $attribute => $message,
            ]);

            if ($default->fails()) $fail(':Attribute exact dimension must be ' . $this->constraints['width'] . 'px x ' . $this->constraints['height'] . 'px.');
        }

        $message = $this->__toString('ratio');

        if ($message) {
            $ratio = Validator::make([
                $attribute => $value
            ], [
                $attribute => $message,
            ]);

            if ($ratio->fails()) $fail(':Attribute dimension ratio must be ' . $this->constraints['ratio'] . '.');
        }
    }
}
