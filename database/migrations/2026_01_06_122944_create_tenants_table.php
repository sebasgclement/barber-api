<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            
            // Datos de Identidad
            $table->string('name'); // Ej: "Barbería Sebas"
            $table->string('slug')->unique(); // Para la URL: app.com/barberia-sebas
            
            // Configuración Visual (Marca Blanca) 
            $table->string('primary_color')->default('#000000'); // Para pintar los botones
            $table->string('logo_url')->nullable(); // URL del logo si suben uno
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};