<?php

namespace Modules\Employee\Http\Controllers;

use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function index()
    {
        return redirect()->route('dashboard');
    }
}
