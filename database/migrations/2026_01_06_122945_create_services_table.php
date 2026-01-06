<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            
            // Relación Multi-Tenant 
            // Si se borra la barbería, se borran sus servicios (cascade)
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade');
            
            $table->string('name'); // Ej: "Corte y Barba"
            $table->text('description')->nullable(); 
            $table->decimal('price', 10, 2); // Ej: 5000.00
            $table->integer('duration_minutes'); // Ej: 45 (Clave para calcular agenda)
            $table->boolean('is_active')->default(true); // Para ocultar servicios sin borrarlos
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};