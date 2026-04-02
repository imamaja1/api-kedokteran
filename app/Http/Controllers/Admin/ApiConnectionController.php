<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use Illuminate\Http\Request;

class ApiConnectionController extends Controller
{
    private function requireAdmin()
    {
        if (! auth()->user()->isAdmin()) {
            abort(403, 'Hanya admin yang dapat mengelola API Connections.');
        }
    }

    public function index()
    {
        $this->requireAdmin();
        $connections = ApiConnection::all();

        return view('admin.connections.index', compact('connections'));
    }

    public function create()
    {
        $this->requireAdmin();

        return view('admin.connections.create');
    }

    public function store(Request $request)
    {
        $this->requireAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_url' => 'required|url|max:500',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        ApiConnection::create($data);

        return redirect()->route('admin.connections.index')
            ->with('success', 'API Connection berhasil ditambahkan.');
    }

    public function edit(ApiConnection $connection)
    {
        $this->requireAdmin();

        return view('admin.connections.edit', compact('connection'));
    }

    public function update(Request $request, ApiConnection $connection)
    {
        $this->requireAdmin();

        $data = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'base_url' => 'required|url|max:500',
            'username' => 'nullable|string|max:255',
            'password' => 'nullable|string|max:500',
            'is_active' => 'nullable|boolean',
        ]);

        $data['is_active'] = $request->boolean('is_active');

        // Jika password dikosongkan, jangan timpa password lama
        if (empty($data['password'])) {
            unset($data['password']);
        }

        $connection->update($data);

        return redirect()->route('admin.connections.index')
            ->with('success', 'API Connection berhasil diperbarui.');
    }

    public function destroy(ApiConnection $connection)
    {
        $this->requireAdmin();
        $connection->delete();

        return redirect()->route('admin.connections.index')
            ->with('success', 'API Connection berhasil dihapus.');
    }
}
