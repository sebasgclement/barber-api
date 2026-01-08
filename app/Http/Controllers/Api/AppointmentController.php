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

    // 2. CREAR TURNO
    public function store(Request $request)
    {
        $request->validate([
            'service_id' => 'required|exists:services,id',
            'scheduled_at' => 'required|date_format:Y-m-d H:i:s',
            'customer_name' => 'required|string',
            'customer_phone' => 'required|string',
            'customer_email' => 'nullable|email',
        ]);

        $startTime = Carbon::parse($request->scheduled_at);
        
        if ($startTime->hour < 9 || $startTime->hour >= 20) {
            return response()->json(['message' => 'La barbería está cerrada a esa hora.'], 422);
        }

        $service = Service::find($request->service_id);
        $minutes = $service->duration_minutes ?? 60; 
        $endTime = $startTime->copy()->addMinutes($minutes);

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
            return response()->json(['message' => '¡Ups! Ese horario ya está ocupado.'], 422);
        }

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

    // 5. MOSTRAR LA PANTALLA DE MIS TURNOS
    public function renderPage()
    {
        return Inertia::render('MyAppointments');
    }

    // 6. FUNCIONES DE ADMIN (MODIFICADO PARA SEPARAR HOY/FUTURO)
    public function adminList()
    {
        if (!auth()->user()->is_admin) {
            abort(403, 'Acceso denegado.');
        }

        $today = Carbon::today();

        // Turnos de HOY
        $todayAppointments = Appointment::with('service')
            ->whereDate('scheduled_at', $today)
            ->orderBy('scheduled_at', 'asc')
            ->get();

        // Turnos FUTUROS (Mañana en adelante)
        $futureAppointments = Appointment::with('service')
            ->whereDate('scheduled_at', '>', $today)
            ->orderBy('scheduled_at', 'asc')
            ->get();

        return response()->json([
            'today' => $todayAppointments,
            'future' => $futureAppointments
        ]);
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

    // 8. GENERADOR DE HORARIOS DISPONIBLES (VERSIÓN MEJORADA 2.0)
    public function availableSlots(Request $request)
    {
        $request->validate([
            'date' => 'required|date_format:Y-m-d',
            'service_id' => 'required|exists:services,id'
        ]);

        $date = $request->date;
        $service = Service::find($request->service_id);
        $duration = $service->duration_minutes ?? 60;

        // Definir hora de inicio y fin de la jornada laboral
        $startOfDay = Carbon::parse("$date 09:00:00");
        $endOfDay = Carbon::parse("$date 20:00:00");

        // Obtener la hora actual para saber qué ya pasó
        $now = Carbon::now();
        $isToday = Carbon::parse($date)->isToday();

        $slots = [];
        $currentSlot = $startOfDay->copy();

        // Traer turnos ocupados
        $appointments = Appointment::where('tenant_id', 1)
            ->whereDate('scheduled_at', $date)
            ->where('payment_status', '!=', 'cancelled')
            ->get(['scheduled_at', 'ends_at']);

        while ($currentSlot->copy()->addMinutes($duration)->lte($endOfDay)) {
            
            $slotStart = $currentSlot;
            $slotEnd = $currentSlot->copy()->addMinutes($duration);

            // 1. Chequeo de ocupado por otro turno
            $isBooked = $appointments->contains(function ($app) use ($slotStart, $slotEnd) {
                return $app->scheduled_at < $slotEnd && $app->ends_at > $slotStart;
            });

            // 2. Chequeo de horario pasado (NUEVO)
            // Si es hoy Y la hora de inicio del slot ya pasó, está no disponible.
            $isPast = $isToday && $slotStart->lt($now);

            // Un slot está disponible solo si NO está ocupado Y NO es pasado
            $isAvailable = !$isBooked && !$isPast;

            // Guardamos el objeto completo
            $slots[] = [
                'time' => $slotStart->format('H:i'),
                'available' => $isAvailable,
                // Opcional: podemos mandar la razón para mostrar en el front
                'reason' => $isBooked ? 'Ocupado' : ($isPast ? 'Horario pasado' : 'Disponible')
            ];

            // Avanzamos de a 30 mins para mostrar opciones
            $currentSlot->addMinutes(30);
        }

        return response()->json($slots);
    }
}