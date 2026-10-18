<?php

namespace App\Rules;

use App\Support\ContactFilter;
use Illuminate\Contracts\Validation\Rule;

/** Free text people read on Himma (profile, packages, check-ins, reviews) can't carry a phone, an account, a link or another app. */
class NoContactDetails implements Rule
{
    public function passes($attribute, $value): bool
    {
        return !is_string($value) || ContactFilter::check($value) === null;
    }

    public function message(): string
    {
        return __('Phone numbers, accounts, links and other apps can\'t be shared on Himma. Contact and payment stay on Himma, to protect coaches and clients.');
    }
}
