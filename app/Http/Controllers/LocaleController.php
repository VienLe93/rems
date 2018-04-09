<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Session;
use URL;

class LocaleController extends Controller
{
    public function setLocale($locale = 'vi')
    {
        if (in_array($locale, ['en', 'vi']))
        {
        	Session::put('locale', $locale);
        }

        return redirect(url(URL::previous()));
    }
}
