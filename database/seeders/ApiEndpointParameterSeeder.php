<?php

namespace Database\Seeders;

use App\Service\DocsSyncService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ApiEndpointParameterSeeder extends Seeder
{
    public function run(): void
    {
        if (! DB::connection()->getSchemaBuilder()->hasTable('api_endpoint_parameters')) {
            return;
        }

        (new DocsSyncService())->sync();
    }
}
