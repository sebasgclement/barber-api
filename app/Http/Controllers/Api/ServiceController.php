<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Service;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    public function index()
    {
        // Traer servicios de la Barbería 1 que estén ACTIVOS
        $services = Service::where('tenant_id', 1)
            ->where('is_active', true)
            ->get(['id', 'name', 'price', 'duration_minutes', 'description']);

        return response()->json($services);
    }
}