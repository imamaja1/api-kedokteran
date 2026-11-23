<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiEndpoint;
use App\Models\ApiEndpointParameter;
use App\Models\ApiSection;
use App\Service\DocsSyncService;
use Illuminate\Http\Request;

class ApiEndpointController extends Controller
{
    public function index()
    {
        $endpoints = ApiEndpoint::with('section')
            ->withCount('parameters')
            ->orderBy('api_section_id')
            ->orderBy('sort_order')
            ->get();
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
        $endpoint->load('parameters.sourceEndpoint');
        $sections = ApiSection::orderBy('sort_order')->get();
        $allEndpoints = ApiEndpoint::orderBy('url')->orderBy('method')->get(['id', 'method', 'url', 'title']);

        return view('admin.endpoints.edit', compact('endpoint', 'sections', 'allEndpoints'));
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
            'parameters' => 'nullable|array',
            'parameters.*.name' => 'required_with:parameters|string|max:100',
            'parameters.*.type' => 'required_with:parameters|in:string,integer,number,boolean,array',
            'parameters.*.location' => 'required_with:parameters|in:body,query,path',
            'parameters.*.is_required' => 'nullable|boolean',
            'parameters.*.source_type' => 'required_with:parameters|in:input,endpoint,session',
            'parameters.*.source_endpoint_id' => 'nullable|exists:api_endpoints,id',
            'parameters.*.source_field' => 'nullable|string|max:100',
            'parameters.*.example' => 'nullable|string',
        ]);

        $data['sort_order'] = $data['sort_order'] ?? 0;
        $paramsInput = $data['parameters'] ?? null;
        unset($data['parameters']);

        $endpoint->update($data);

        if ($paramsInput !== null) {
            $endpoint->parameters()->delete();
            $sort = 1;
            foreach ($paramsInput as $p) {
                $name = trim((string) ($p['name'] ?? ''));
                if ($name === '') {
                    continue;
                }
                ApiEndpointParameter::create([
                    'api_endpoint_id' => $endpoint->id,
                    'name' => $name,
                    'type' => $p['type'] ?? 'string',
                    'location' => $p['location'] ?? 'body',
                    'is_required' => ! empty($p['is_required']),
                    'source_type' => $p['source_type'] ?? 'input',
                    'source_endpoint_id' => ($p['source_type'] ?? 'input') === 'endpoint' ? ($p['source_endpoint_id'] ?: null) : null,
                    'source_field' => $p['source_field'] ?? null,
                    'example' => $p['example'] ?? null,
                    'sort_order' => $sort++,
                ]);
            }
        }

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'Endpoint dan parameter berhasil diperbarui.');
    }

    public function syncDocs(DocsSyncService $syncService)
    {
        $summary = $syncService->sync();

        return redirect()->route('admin.endpoints.index')
            ->with('success', "Sinkronisasi dokumentasi selesai: {$summary['cleaned_endpoints']} body dibersihkan, {$summary['removed_duplicates']} duplikat dihapus, {$summary['synced_parameters']} parameter diselaraskan.");
    }

    public function destroy(ApiEndpoint $endpoint)
    {
        $endpoint->delete();

        return redirect()->route('admin.endpoints.index')
            ->with('success', 'Endpoint berhasil dihapus.');
    }
}
