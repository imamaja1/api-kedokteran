<?php

namespace App\Service;

class ServiceTahunAngkatan
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }
    public function getTahunAngkatan()
    {
        $data = [];
        for ($year = 2025; $year <= date('Y'); $year++) {
            $data[] = [
                'tahun_angkatan' => $year,
                'label' => 'Angkatan ' . $year
            ];
        }
        return response()->json([
            'status' => true,
            'message' => 'API Tahun Angkatan',
            'data' => $data
        ]);
    }
}
