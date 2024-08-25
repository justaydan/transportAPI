<?php

namespace App\Rules;

use App\Models\City;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidAddressCombination implements ValidationRule
{
    /**
     * @param string $attribute
     * @param mixed $value
     * @param Closure $fail
     * @return void
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!City::query()->where('country', $value['country'])
            ->where('zipCode', $value['zip'])
            ->where('name', $value['city'])
            ->exists())
            $fail('The provided combination of country, zip, and city does not exist.');
    }
}
