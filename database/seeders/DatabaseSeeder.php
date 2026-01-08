<?php

namespace Database\Seeders;

use App\Models\Service;
use App\Models\Tenant;
use App\Models\User; // <--- Importante
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Crear el DUEÑO primero (para tener el ID)
        $user = User::create([
            'name' => 'Sebas Admin',
            'email' => 'sebas@test.com', // Este será tu usuario para entrar
            'password' => Hash::make('password'),
            'is_admin' => true, 
        ]);

        // 2. Crear la Barbería vinculada a ese dueño
        $tenant = Tenant::create([
            'user_id' => $user->id, // <--- AQUÍ SOLUCIONAMOS EL ERROR
            'name' => 'Barbería Sebas',
            'slug' => 'barberia-sebas',
            'primary_color' => '#000000',
        ]);

        // 3. Crear Servicios de prueba vinculados a esa barbería
        Service::create([
            'tenant_id' => $tenant->id,
            'name' => 'Corte Clásico',
            'description' => 'Corte con tijera y máquina',
            'price' => 5000.00,
            'duration_minutes' => 45,
            'is_active' => true,
        ]);

        $this->command->info('¡Base de datos reseteada! Usuario: sebas@test.com / password');
    }
}