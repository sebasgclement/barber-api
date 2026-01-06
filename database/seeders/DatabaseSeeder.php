<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear una Barbería de prueba
        $tenant = Tenant::create([
            'name' => 'Barbería Sebas',
            'slug' => 'barberia-sebas',
            'primary_color' => '#000000',
        ]);

        // 2. Crear un Servicio de prueba vinculado a esa barbería
        Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Corte Clásico',
            'description' => 'Corte con tijera y máquina',
            'price' => 5000.00,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        // (Opcional) Mensaje en consola para saber que corrió
        $this->command->info('¡Barbería y Servicio de prueba creados!');
    }
}