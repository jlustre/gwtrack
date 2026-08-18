<?php

namespace App\Rules;

use App\Support\ResourceUrl;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UrlOrRelativePath implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! ResourceUrl::isValid(is_string($value) ? $value : null)) {
            $fail('Enter a full URL or a site-relative path such as resources/documents/welcome-packet.pdf.');
        }
    }
}
