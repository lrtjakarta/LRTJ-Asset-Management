<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Yajra\DataTables\Facades\DataTables;


class AssetsController extends Controller
{
    /** Page */
    public function index()
    {
        return view('assets.assets');
    }

}
