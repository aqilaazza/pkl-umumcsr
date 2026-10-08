<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.home',
            'need_apexcharts' => true,
        ]);
    }
}
