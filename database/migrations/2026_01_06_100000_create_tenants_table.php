<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
{
    Schema::create('tenants', function (Blueprint $table) {
        $table->id();
        // El dueño de la barbería
        $table->foreignId('user_id')->constrained()->onDelete('cascade'); 
        
        $table->string('name');
        $table->string('slug')->unique(); // Para la URL (ej: turno.com/barberia-sur)
        
        // TUS CAMPOS DE MARCA
        $table->string('primary_color')->default('#d97706'); // Dorado por defecto
        $table->string('logo_url')->nullable();
        
        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
