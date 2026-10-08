<?php

namespace App\Http\Controllers\Sdm;

use App\Http\Controllers\Controller;

class ApprovalManagerController extends Controller
{
    public function index()
    {
        return view('layouts.sdm', [
            'pageView' => 'sdm.pages.approval_laporan_manager',
            'need_datatables' => true,
        ]);
    }
}
