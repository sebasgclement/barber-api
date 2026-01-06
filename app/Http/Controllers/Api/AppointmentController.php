<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\Request;
use Carbon\Carbon; // Importamos Carbon para manejar fechas

class AppointmentController extends Controller
{
    // 1. LISTAR TURNOS (Solo mira, no toca)
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        // Buscamos turnos de ESTA barbería en ESA fecha
        $appointments = Appointment::where('tenant_id', 1)
            ->whereDate('scheduled_at', $request->date)
            ->get(['id', 'scheduled_at', 'payment_status']);

        return response()->json($appointments);
    }

    // 2. CREAR TURNO (Acá va la seguridad)
    public function store(Request $request)
    {
        // A. Validar datos básicos
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_email' => 'nullable|email',
        ]);

        // B. VALIDAR HORARIO COMERCIAL (EL PATOVICA) 🛑
        // -------------------------------------------------------
        // AHORA SÍ ESTÁ EN EL LUGAR CORRECTO
        $date = Carbon::parse($request->scheduled_at);
        
        // Si es antes de las 9 o después de las 20
        if ($date->hour < 9 || $date->hour >= 20) {
            return response()->json([
                'message' => 'La barbería está cerrada a esa hora. Horario: 09:00 a 20:00.'
            ], 422);
        }
        // -------------------------------------------------------

        // C. VALIDAR DISPONIBILIDAD (Evitar choque de turnos)
        $exists = Appointment::where('tenant_id', 1)
                    ->where('scheduled_at', $request->scheduled_at)
                    ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Lo sentimos, ese horario ya está ocupado.'
            ], 409);
        }

        // D. BUSCAR EL PRECIO REAL
        $service = Service::find($request->service_id);

        // E. GUARDAR
        $appointment = Appointment::create([
            'tenant_id' => 1,
            'service_id' => $request->service_id,
            'scheduled_at' => $request->scheduled_at,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'payment_status' => 'pending',
            'total_amount' => $service->price,
        ]);

        return response()->json([
            'message' => 'Turno reservado con éxito. Esperando pago.',
            'data' => $appointment
        ], 201);
    }
}