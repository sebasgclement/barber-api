<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AppointmentController;
use App\Http\Controllers\Api\ServiceController;

// RUTAS PÚBLICAS (Cualquiera puede ver servicios y horarios ocupados)
Route::get('/services', [ServiceController::class, 'index']);
Route::get('/appointments', [AppointmentController::class, 'index']); // Ver disponibilidad

// RUTAS PROTEGIDAS (Solo usuarios logueados)
Route::middleware(['auth:sanctum'])->group(function () {
    
    // Usuario actual
    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    // Reservar un turno (Ahora exigimos login para reservar, es más seguro)
    Route::post('/appointments', [AppointmentController::class, 'store']);

    // Ver MIS turnos
    Route::get('/my-appointments', [AppointmentController::class, 'myAppointments']);

    // Cancelar MI turno (Para el desafío)
    Route::put('/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);
});