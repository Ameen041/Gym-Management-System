<?php

namespace App\Http\Controllers;

/** Terms & conditions and privacy policy (the text is in resources/views/legal, one file per language). */
class LegalController extends Controller
{
    // Change it whenever the text changes
    public const UPDATED = '2026-10-18';

    public function terms()
    {
        return view('legal.terms');
    }

    public function privacy()
    {
        return view('legal.privacy');
    }
}
