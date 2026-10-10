<?php

use App\Models\User;
use Database\Seeders\JadwalDocsSeeder;
use Illuminate\Support\Facades\DB;

test('admin can access /admin/docs and sees three resizable panel elements', function () {
    // Ensure docs data is available
    if (DB::table('api_sections')->count() === 0) {
        $this->seed(JadwalDocsSeeder::class);
    }

    $admin = User::first() ?? User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/docs');

    $response->assertStatus(200);

    // Verify gutters and IDs
    $response->assertSee('id="workspace-container"', false);
    $response->assertSee('id="col-sidebar"', false);
    $response->assertSee('id="gutter-left"', false);
    $response->assertSee('id="col-specs"', false);
    $response->assertSee('id="gutter-right"', false);
    $response->assertSee('id="col-tester"', false);

    // Verify accessibility attributes (PRD Section 11)
    $response->assertSee('role="separator"', false);
    $response->assertSee('aria-orientation="vertical"', false);
    $response->assertSee('aria-label="Pemisah panel seksi dan endpoint"', false);
    $response->assertSee('aria-label="Pemisah panel live API tester"', false);

    // Verify layout buttons (PRD F-07 and F-11)
    $response->assertSee('id="btn-reset-layout"', false);
    $response->assertSee('id="btn-toggle-tester"', false);

    // Verify local storage key and script initialization
    $response->assertSee('siska_docs_panel_layout_v1', false);
    $response->assertSee('initPanelLayout()', false);
});
