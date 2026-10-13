<?php

use App\Models\User;
use Database\Seeders\JadwalDocsSeeder;
use Database\Seeders\ApiEndpointParameterSeeder;
use Illuminate\Support\Facades\DB;

test('admin can access /admin/docs and sees workflows and parameter source columns', function () {
    if (DB::table('api_sections')->count() === 0) {
        $this->seed(JadwalDocsSeeder::class);
    }
    if (DB::table('api_endpoint_parameters')->count() === 0) {
        $this->seed(ApiEndpointParameterSeeder::class);
    }

    $admin = User::first() ?? User::factory()->create(['role' => 'admin']);

    $response = $this->actingAs($admin)->get('/admin/docs');

    $response->assertStatus(200);

    // Verify workflows section in sidebar
    $response->assertSee('id="workflows-sidebar-section"', false);
    $response->assertSee('Alur Bisnis Utama', false);

    // Verify prerequisite stepper and workflow view containers
    $response->assertSee('id="spec-prerequisite-card"', false);
    $response->assertSee('id="workflow-spec-view"', false);

    // Verify Asal Nilai column
    $response->assertSee('Asal Nilai (Source)', false);

    // Verify active banner and in-place switcher functions
    $response->assertSee('id="spec-prereq-active-banner"', false);
    $response->assertSee('id="spec-prereq-active-name"', false);
    $response->assertSee('restoreMainEndpoint()', false);
    $response->assertSee('function selectPrerequisiteStep(', false);
    $response->assertSee('function getPrerequisiteChain(', false);
    $response->assertSee('function renderDynamicSubSpec(', false);

    // Verify exclusive branching diagram (Fork & Join DAG with SVG Bézier curves) elements
    $response->assertSee('id="spec-prereq-branching-wrap"', false);
    $response->assertSee('id="spec-prereq-branch-badge"', false);
    $response->assertSee('function buildBranchingHtml(', false);
    $response->assertSee('function renderDagConnectors(', false);
    $response->assertSee('function togglePrereqViewMode(', false);
});

test('api endpoint model includes auth_role attribute and parameters relationship', function () {
    if (\App\Models\ApiEndpoint::count() === 0) {
        $this->seed(\Database\Seeders\JadwalDocsSeeder::class);
    }
    $ep = \App\Models\ApiEndpoint::first();
    expect($ep)->not->toBeNull();

    $array = $ep->toArray();
    expect($array)->toHaveKey('auth_role');
    expect($ep->parameters())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
});
