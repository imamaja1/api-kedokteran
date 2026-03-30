<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Models\ApiSection;
use Illuminate\Http\Request;

class ApiEndpointController extends Controller
{
    public function index()
    {
        $endpoints = ApiEndpoint::with('section')->orderBy('api_section_id')->orderBy('sort_order')->get();
        $sections = ApiSection::orderBy('sort_order')->get();

        return view('admin.endpoints.index', compact('endpoints', 'sections'));
    }

    public function create()
    {
        $sections = ApiSection::orderBy('sort_order')->get();

        return view('admin.endpoints.create', compact('sections'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'api_section_id' => 'required|exists:api_sections,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'url' => 'required|string|max:500',
            'headers' => 'nullable|string',
            'body' => 'nullable|string',
            'response_example' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        ApiEndpoint::create($data);

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'Endpoint berhasil ditambahkan.');
    }

    public function edit(ApiEndpoint $endpoint)
    {
        $sections = ApiSection::orderBy('sort_order')->get();

        return view('admin.endpoints.edit', compact('endpoint', 'sections'));
    }

    public function update(Request $request, ApiEndpoint $endpoint)
    {
        $data = $request->validate([
            'api_section_id' => 'required|exists:api_sections,id',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'method' => 'required|in:GET,POST,PUT,PATCH,DELETE',
            'url' => 'required|string|max:500',
            'headers' => 'nullable|string',
            'body' => 'nullable|string',
            'response_example' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $endpoint->update($data);

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'Endpoint berhasil diperbarui.');
    }

    public function destroy(ApiEndpoint $endpoint)
    {
        $endpoint->delete();

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'Endpoint berhasil dihapus.');
    }
}
