<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service; // <--- IMPORTANTE: Agregamos esto para poder buscar el precio
use Illuminate\Http\Request;

class AppointmentController extends Controller
{

    public function index(Request $request)
    {
        // Validamos que nos manden la fecha que quieren mirar
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        // Buscamos turnos de ESTA barbería (ID 1) en ESA fecha
        $appointments = Appointment::where('tenant_id', 1)
            ->whereDate('scheduled_at', $request->date)
            ->get(['id', 'scheduled_at', 'payment_status']); // Solo devolvemos lo necesario

        return response()->json($appointments);
    }

    public function store(Request $request)
    {
        // 1. VALIDAR DATOS DE ENTRADA
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_email' => 'nullable|email',
        ]);

        // 2. VALIDAR DISPONIBILIDAD (Evitar choque de turnos)
        // Buscamos si ya existe un turno a esa misma hora en esta barbería
        $exists = Appointment::where('tenant_id', 1)
                    ->where('scheduled_at', $request->scheduled_at)
                    ->exists();

        if ($exists) {
            return response()->json([
                'message' => 'Lo sentimos, ese horario ya está ocupado.'
            ], 409); // 409 = Conflict
        }

        // 3. BUSCAR EL PRECIO REAL DEL SERVICIO
        $service = Service::find($request->service_id);

        // 4. CREAR EL TURNO
        $appointment = Appointment::create([
            'tenant_id' => 1,
            'service_id' => $request->service_id,
            'scheduled_at' => $request->scheduled_at,
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'payment_status' => 'pending',
            'total_amount' => $service->price, // <--- Usamos el precio de la base de datos
        ]);

        // 5. RESPONDER
        return response()->json([
            'message' => 'Turno reservado con éxito. Esperando pago.',
            'data' => $appointment
        ], 201);
    }
}