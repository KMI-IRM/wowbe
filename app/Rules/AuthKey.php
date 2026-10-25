<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;
use Illuminate\Validation\Rules;

class AuthKey implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     *
     * @SuppressWarnings("PHPMD.UnusedFormalParameter")
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        // Check if the authentication key is a PIN-code
        if (Str::length($value) === 6 && preg_match('/^\d{6}$/', $value) === 1) {
            $validator = Validator::make(['auth_key' => $value], [
                'auth_key' => ['digits:6'],
            ]);

            if ($validator->fails() === true) {
                $fail($validator->messages()->first('auth_key')); // @codeCoverageIgnore
            }
        }
        // Check if the authentication key is a password
        else {
            $validator = Validator::make(['auth_key' => $value], [
                'auth_key' => Rules\Password::defaults(),
            ]);

            if ($validator->fails() === true) {
                $fail($validator->messages()->first('auth_key'));
            }
        }
    }
}
