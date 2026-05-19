<?php

namespace App\Http\Controllers\Api_Staff;

use App\Http\Controllers\Controller;
use App\Service\ServiceTahunAngkatan;

class DefaultController extends Controller
{
    public function tahun_angkatan()
    {
        return (new ServiceTahunAngkatan)->getTahunAngkatan();
    }
}
