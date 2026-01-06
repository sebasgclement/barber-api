<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Appointment;
use App\Models\Service;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Inertia\Inertia;

class AppointmentController extends Controller
{
    // 1. LISTAR TURNOS (Para el calendario de reservas)
    public function index(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
        ]);

        $appointments = Appointment::where('tenant_id', 1)
            ->whereDate('scheduled_at', $request->date)
            ->where('payment_status', '!=', 'cancelled')
            ->get(['id', 'scheduled_at', 'ends_at', 'payment_status']);

        return response()->json($appointments);
    }

    // 2. CREAR TURNO (Corregido)
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_email' => 'nullable|email',
        ]);

        // A. Definimos inicio
        $startTime = Carbon::parse($request->scheduled_at);
        
        // Validar horario comercial (9 a 20hs)
        if ($startTime->hour < 9 || $startTime->hour >= 20) {
            return response()->json(['message' => 'La barbería está cerrada a esa hora.'], 422);
        }

        $service = Service::find($request->service_id);
        
        // CORRECCIÓN AQUÍ: Usamos la duración real de la DB
        $minutes = $service->duration_minutes ?? 60; 
        
        // Calculamos el final sumando esos minutos
        $endTime = $startTime->copy()->addMinutes($minutes);

        // B. CHECK DE SUPERPOSICIÓN
        $overlap = Appointment::where('tenant_id', 1)
            ->where('payment_status', '!=', 'cancelled')
            ->where(function ($query) use ($startTime, $endTime) {
                $query->where(function ($q) use ($startTime, $endTime) {
                    $q->where('scheduled_at', '<', $endTime)
                      ->where('ends_at', '>', $startTime);
                });
            })
            ->exists();

        if ($overlap) {
            return response()->json(['message' => '¡Ups! Ese horario ya está ocupado (o se superpone con otro).'], 422);
        }

        // C. Guardamos
        $appointment = Appointment::create([
            'tenant_id' => 1,
            'service_id' => $request->service_id,
            'scheduled_at' => $startTime,
            'ends_at' => $endTime, 
            'customer_name' => $request->customer_name,
            'customer_phone' => $request->customer_phone,
            'customer_email' => $request->customer_email,
            'payment_status' => 'pending',
            'total_amount' => $service->price,
        ]);

        return response()->json(['message' => 'Turno reservado con éxito.', 'data' => $appointment], 201);
    }

    // 3. DATOS DE MIS TURNOS
    public function myAppointments(Request $request)
    {
        $appointments = Appointment::with('service')
            ->where('customer_email', $request->user()->email)
            ->orderBy('scheduled_at', 'desc')
            ->get();

        return response()->json($appointments);
    }

    // 4. CANCELAR TURNO
    public function cancel(Request $request, $id)
    {
        $query = Appointment::where('id', $id);

        if (!$request->user()->is_admin) {
            $query->where('customer_email', $request->user()->email);
        }

        $appointment = $query->firstOrFail();

        $appointment->payment_status = 'cancelled';
        $appointment->save();

        return response()->json(['message' => 'Turno cancelado correctamente.']);
    }

    // 5. MOSTRAR LA PANTALLA
    public function renderPage()
    {
        return Inertia::render('MyAppointments');
    }

    // 6. FUNCIONES DE ADMIN
    public function adminList()
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Acceso denegado.');
        }

        $appointments = Appointment::with('service')
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json($appointments);
    }

    // 7. MARCAR COMO PAGADO
    public function markAsPaid($id)
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'No sos el jefe.');
        }

        $appointment = Appointment::findOrFail($id);
        $appointment->payment_status = 'paid';
        $appointment->save();

        return response()->json(['message' => 'Turno cobrado.', 'appointment' => $appointment]);
    }

    // 8. GENERADOR DE HORARIOS DISPONIBLES
    public function availableSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'service_id' => 'required|exists:services,id'
        ]);

        $date = $request->date;
        $service = Service::find($request->service_id);
        
        $duration = $service->duration_minutes ?? 60; 

        // Rango de atención: 09:00 a 20:00
        $startOfDay = Carbon::parse("$date 09:00:00");
        $endOfDay = Carbon::parse("$date 20:00:00");

        $slots = [];
        $currentSlot = $startOfDay->copy();

        $appointments = Appointment::where('tenant_id', 1)
            ->whereDate('scheduled_at', $date)
            ->where('payment_status', '!=', 'cancelled')
            ->get(['scheduled_at', 'ends_at']);

        while ($currentSlot->copy()->addMinutes($duration)->lte($endOfDay)) {
            
            $slotStart = $currentSlot;
            $slotEnd = $currentSlot->copy()->addMinutes($duration);

            $isBooked = $appointments->contains(function ($app) use ($slotStart, $slotEnd) {
                return $app->scheduled_at < $slotEnd && $app->ends_at > $slotStart;
            });

            if (!$isBooked) {
                $slots[] = $slotStart->format('H:i'); 
            }

            // CORRECCIÓN: Avanzamos de a 30 mins para dar más opciones de inicio
            // (Ej: 9:00, 9:30, 10:00) aunque el turno dure 2 horas.
            $currentSlot->addMinutes(30); 
        }

        return response()->json($slots);
    }
}