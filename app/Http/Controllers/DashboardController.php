<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;

class DashboardController
{
    public function dashboard()
    {
        return view('dashboard');
    }

}
