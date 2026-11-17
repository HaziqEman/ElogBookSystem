<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminDashboardService;
use Illuminate\Contracts\View\View;

class AdminController extends Controller
{
    public function dashboard(AdminDashboardService $dashboard): View
    {
        return view('admin.dashboard', $dashboard->dashboardData());
    }
}
