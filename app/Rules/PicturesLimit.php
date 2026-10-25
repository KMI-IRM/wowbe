<?php

namespace App\Rules;

use App\Models\Site;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Translation\PotentiallyTranslatedString;

class PicturesLimit implements ValidationRule
{
    public function __construct(
        private Site $site,
        private string $collection
    ) {}

    /**
     * Run the validation rule.
     *
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $count = $this->site->getMedia($this->collection)->count();

        if ($count + 1 >= 5) {
            $fail('You can only upload a maximum of 4 pictures.');
        }
    }
}
