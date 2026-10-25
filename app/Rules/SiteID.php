<?php

namespace App\Rules;

use App\Models\Site;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Str;
use Illuminate\Translation\PotentiallyTranslatedString;

class SiteID implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Str::isUuid($value) === true) {
            // Check if the UUID exists in the sites table
            if (Site::where('id', $value)->exists() !== true) {
                $fail('The :attribute must be a valid site UUID.');
            }
        } else {
            // Check if the short ID exists in the sites table
            if (Site::where('short_id', $value)->exists() !== true) {
                $fail('The :attribute must be a valid site short ID.');
            }
        }
    }
}
