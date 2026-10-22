<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;

class HelpController extends Controller
{
    public function page()
    {
        return view('help.page');
    }

    public function pdf()
    {
        return Pdf::loadView('help.pdf')->setPaper('a4')->download('agency-guide.pdf');
    }
}
