<?php

namespace App\Http\Controllers\Manager;

use App\Http\Controllers\Controller;

class HomeController extends Controller
{
    public function index()
    {
        return view('layouts.manager', [
            'pageView' => 'manager.pages.home',
            'need_apexcharts' => true,
        ]);
    }
}