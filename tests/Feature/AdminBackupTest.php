<?php

namespace Tests\Feature;

use App\Models\ApiEndpoint;
use App\Models\ApiEndpointParameter;
use App\Models\ApiSection;
use App\Models\ApiWorkflow;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Helpers\TestDataHelper;
use Tests\TestCase;

class AdminBackupTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create(['role' => 'admin']);
        $this->staff = TestDataHelper::createStaff();
    }

    public function test_guest_cannot_access_backup_page(): void
    {
        $response = $this->get('/admin/backup');
        $response->assertRedirect('/login');
    }

    public function test_admin_and_staff_can_view_backup_page(): void
    {
        // Admin
        $resAdmin = $this->actingAs($this->admin)->get('/admin/backup');
        $resAdmin->assertStatus(200)
            ->assertSee('Backup &amp; Restore (Database &amp; Dokumentasi)', false)
            ->assertSee('Export Database (.sql)')
            ->assertSee('Import Database (.sql)')
            ->assertSee('Export Dokumentasi API (.json)')
            ->assertSee('Import Dokumentasi API (.json)');

        // Staff
        $resStaff = $this->actingAs($this->staff)->get('/admin/backup');
        $resStaff->assertStatus(200)
            ->assertSee('Backup &amp; Restore (Database &amp; Dokumentasi)', false);
    }

    public function test_export_docs_returns_json_stream(): void
    {
        $section = ApiSection::create(['title' => 'Test Section', 'sort_order' => 1]);
        $endpoint = ApiEndpoint::create([
            'api_section_id' => $section->id,
            'title'          => 'Test Endpoint',
            'method'         => 'GET',
            'url'            => '/api/test',
            'sort_order'     => 1,
        ]);
        ApiEndpointParameter::create([
            'api_endpoint_id' => $endpoint->id,
            'name'            => 'param1',
            'type'            => 'string',
            'sort_order'      => 1,
        ]);
        ApiWorkflow::create([
            'id'         => 'wf-test',
            'title'      => 'Workflow Test',
            'summary'    => 'Test summary',
            'roles'      => ['Staff'],
            'rules'      => ['Rule 1'],
            'phases'     => [],
            'sort_order' => 1,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/backup/export/docs');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/json');

        $content = $response->streamedContent();
        $data = json_decode($content, true);

        $this->assertIsArray($data);
        $this->assertEquals('api_documentation', $data['export_type']);
        $this->assertNotEmpty($data['api_sections']);
        $this->assertNotEmpty($data['api_endpoints']);
        $this->assertNotEmpty($data['api_endpoint_parameters']);
        $this->assertNotEmpty($data['api_workflows']);
    }

    public function test_import_docs_restores_documentation(): void
    {
        $jsonPayload = [
            'export_type' => 'api_documentation',
            'api_sections' => [
                ['id' => 901, 'title' => 'Imported Section', 'sort_order' => 1],
            ],
            'api_endpoints' => [
                [
                    'id'             => 901,
                    'api_section_id' => 901,
                    'title'          => 'Imported Endpoint',
                    'method'         => 'POST',
                    'url'            => '/api/imported',
                    'sort_order'     => 1,
                ],
            ],
            'api_endpoint_parameters' => [
                [
                    'id'              => 901,
                    'api_endpoint_id' => 901,
                    'name'            => 'imported_code',
                    'type'            => 'string',
                    'is_required'     => true,
                    'sort_order'      => 1,
                ],
            ],
            'api_workflows' => [
                [
                    'id'         => 'wf-imported',
                    'title'      => 'Imported Workflow',
                    'summary'    => 'Imported workflow summary',
                    'roles'      => ['Admin'],
                    'rules'      => ['Rule 1'],
                    'phases'     => [],
                    'sort_order' => 1,
                    'is_active'  => true,
                ],
            ],
        ];

        $file = UploadedFile::fake()->createWithContent(
            'docs_backup.json',
            json_encode($jsonPayload)
        );

        $response = $this->actingAs($this->admin)->post('/admin/backup/import/docs', [
            'json_file' => $file,
            'mode'      => 'replace',
        ]);

        $response->assertRedirect('/admin/backup')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('api_sections', ['id' => 901, 'title' => 'Imported Section']);
        $this->assertDatabaseHas('api_endpoints', ['id' => 901, 'url' => '/api/imported']);
        $this->assertDatabaseHas('api_endpoint_parameters', ['id' => 901, 'name' => 'imported_code']);
        $this->assertDatabaseHas('api_workflows', ['id' => 'wf-imported']);
    }

    public function test_export_db_returns_sql_stream(): void
    {
        $response = $this->actingAs($this->admin)->get('/admin/backup/export/db');

        $response->assertStatus(200)
            ->assertHeader('content-type', 'application/sql');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Backup Database', $content);
    }

    public function test_import_db_executes_sql_file(): void
    {
        $sqlContent = "
        CREATE TABLE IF NOT EXISTS test_sql_import (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            note VARCHAR(100)
        );
        INSERT INTO test_sql_import (note) VALUES ('Halo dari backup SQL');
        ";

        $file = UploadedFile::fake()->createWithContent(
            'database_backup.sql',
            $sqlContent
        );

        $response = $this->actingAs($this->admin)->post('/admin/backup/import/db', [
            'sql_file' => $file,
        ]);

        $response->assertRedirect('/admin/backup')
            ->assertSessionHas('success');

        $this->assertDatabaseHas('test_sql_import', ['note' => 'Halo dari backup SQL']);
    }
}
