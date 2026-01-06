<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Redirect;

class TenantController extends Controller
{
    // 1. Mostrar el formulario de configuración
    public function edit(Request $request)
    {
        // Obtenemos la barbería del usuario actual
        $tenant = $request->user()->tenant;

        if (!$tenant) {
            // Si por error no tiene (aunque lo creamos en tinker), lo mandamos al inicio
            return Redirect::route('dashboard');
        }

        return Inertia::render('Settings/Edit', [
            'tenant' => $tenant
        ]);
    }

    // 2. Guardar los cambios
    public function update(Request $request)
    {
        $tenant = $request->user()->tenant;

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'primary_color' => 'required|string|regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/', // Valida que sea un color HEX
        ]);

        $tenant->update($validated);

        return Redirect::route('settings.edit')->with('message', '¡Configuración actualizada con éxito! 💈');
    }
}