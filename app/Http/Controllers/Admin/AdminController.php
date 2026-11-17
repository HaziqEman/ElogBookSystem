<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;

class AdminController extends Controller
{
    public function dashboard(AdminDashboardService $dashboard)
    {
        return view('admin.dashboard', $dashboard->dashboardData());
    }
}
