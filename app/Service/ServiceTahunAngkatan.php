<?php

namespace App\Service;

use Illuminate\Http\JsonResponse;

class ServiceTahunAngkatan
{
    public function getTahunAngkatan(): JsonResponse
    {
        $data = [];
        $currentYear = (int) date('Y');

        for ($year = 2025; $year <= $currentYear; $year++) {
            $data[] = [
                'tahun_angkatan' => $year,
                'label' => 'Angkatan ' . $year,
            ];
        }

        return response()->json([
            'status' => true,
            'message' => 'API Tahun Angkatan',
            'data' => $data,
        ]);
    }
}
