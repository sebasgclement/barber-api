<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use App\Http\Controllers\Api\AppointmentController;

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin' => Route::has('login'),
        'canRegister' => Route::has('register'),
        'laravelVersion' => Application::VERSION,
        'phpVersion' => PHP_VERSION,
    ]);
});

Route::get('/dashboard', function () {
    // Si es ADMIN (is_admin = 1), mostramos el panel de dueño
    if (auth()->user()->is_admin) {
        return Inertia::render('AdminDashboard');
    }
    
    // Si es CLIENTE (is_admin = 0), mostramos el formulario de reserva
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // --- RUTAS DE MIS TURNOS ---
    
    // 1. Mostrar la Pantalla (Usamos la nueva función renderPage)
    Route::get('/mis-turnos', [AppointmentController::class, 'renderPage'])->name('my-appointments.index');

    // 2. Obtener los datos JSON (Usamos tu función myAppointments)
    Route::get('/api/my-appointments', [AppointmentController::class, 'myAppointments']);

    // 3. Cancelar turno
    Route::put('/api/appointments/{id}/cancel', [AppointmentController::class, 'cancel']);

    // 4. CREAR TURNO (¡AGREGA ESTA LÍNEA!)
    Route::post('/api/appointments', [AppointmentController::class, 'store']);

    // --- CONFIGURACIÓN DE BARBERÍA ---
    Route::get('/configuracion', [TenantController::class, 'edit'])->name('settings.edit');
    Route::patch('/configuracion', [TenantController::class, 'update'])->name('settings.update');

    // --- RUTA API PARA EL ADMIN ---
    Route::get('/api/admin/appointments', [AppointmentController::class, 'adminList']);
    // Acción de Admin: Marcar pagado
    Route::put('/api/admin/appointments/{id}/pay', [AppointmentController::class, 'markAsPaid']);

    // API: Consultar horarios disponibles
    Route::get('/api/slots', [AppointmentController::class, 'availableSlots']);
});


require __DIR__.'/auth.php';