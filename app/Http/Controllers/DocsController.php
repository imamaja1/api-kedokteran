<?php

namespace App\Http\Controllers;

use App\Models\ApiSection;

class DocsController extends Controller
{
    public function index()
    {
        $sections = ApiSection::with(['endpoints' => fn($q) => $q->orderBy('sort_order')])
            ->orderBy('sort_order')
            ->get();

        return view('docs.index', compact('sections'));
    }
    public function tester()
    {
        return view('docs.tester');
    }
}
