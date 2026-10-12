<?php

namespace App\Http\Controllers;

/**
 * Public help pages: Help Center (per-role guides), FAQs, Cookies policy.
 * Accessible without login so the login page footer can link to them.
 */
class HelpController extends Controller
{
    public function index()
    {
        return view('help.index');
    }

    public function faqs()
    {
        return view('help.faqs');
    }

    public function cookies()
    {
        return view('help.cookies');
    }
}
