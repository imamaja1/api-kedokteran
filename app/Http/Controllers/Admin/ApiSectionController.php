<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiSection;
use Illuminate\Http\Request;

class ApiSectionController extends Controller
{
    public function index()
    {
        $sections = ApiSection::withCount('endpoints')->orderBy('sort_order')->get();
        return view('admin.sections.index', compact('sections'));
    }

    public function create()
    {
        return view('admin.sections.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        ApiSection::create($data);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section berhasil ditambahkan.');
    }

    public function edit(ApiSection $section)
    {
        return view('admin.sections.edit', compact('section'));
    }

    public function update(Request $request, ApiSection $section)
    {
        $data = $request->validate([
            'title'      => 'required|string|max:255',
            'sort_order' => 'nullable|integer',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $section->update($data);

        return redirect()->route('admin.sections.index')
            ->with('success', 'Section berhasil diperbarui.');
    }

    public function destroy(ApiSection $section)
    {
        $section->delete();
        return redirect()->route('admin.sections.index')
            ->with('success', 'Section berhasil dihapus.');
    }
}
