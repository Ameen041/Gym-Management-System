<?php

namespace App\Rules;

use App\Support\ContactFilter;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

/** Free text people read on Himma (profile, packages, check-ins, reviews) can't carry a phone, an account, a link or another app. */
class NoContactDetails implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (is_string($value) && ContactFilter::check($value) !== null) {
            $fail(__('Phone numbers, accounts, links and other apps can\'t be shared on Himma. Contact and payment stay on Himma, to protect coaches and clients.'));
        }
    }
}
