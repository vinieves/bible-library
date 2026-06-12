<?php

namespace App\Http\Controllers;

use Illuminate\View\View;

class PageController extends Controller
{
    public function howToAccess(): View
    {
        return view('pages.how-to-access');
    }

    public function faq(): View
    {
        return view('pages.faq');
    }
}
