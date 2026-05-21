<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceTahunAngkatan;
use Illuminate\Http\JsonResponse;

class DefaultController extends Controller
{
    public function __construct(
        private readonly ServiceTahunAngkatan $service,
    ) {}

    public function tahun_angkatan(): JsonResponse
    {
        return $this->service->getTahunAngkatan();
    }
}
