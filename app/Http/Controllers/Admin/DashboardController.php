<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiSection;
use App\Models\ApiEndpoint;
use App\Models\User;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'sections'  => ApiSection::count(),
            'endpoints' => ApiEndpoint::count(),
            'users'     => User::count(),
        ];

        $recentEndpoints = ApiEndpoint::with('section')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact('stats', 'recentEndpoints'));
    }
}
