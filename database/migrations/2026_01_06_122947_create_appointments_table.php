<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('appointments', function (Blueprint $table) {
            $table->id();
            
            // Relaciones
            $table->foreignId('tenant_id')->constrained()->onDelete('cascade'); // 
            $table->foreignId('service_id')->constrained()->onDelete('cascade');
            
            // Datos del Cliente
            $table->string('customer_name');
            $table->string('customer_phone'); // Necesario para recordatorios por WhatsApp [cite: 27]
            $table->string('customer_email')->nullable(); // Para enviar comprobante MP
            
            // Datos del Turno
            $table->dateTime('scheduled_at'); // Fecha y Hora exacta del turno
            
            // ESTADO DEL PAGO (Anti-Clavos) [cite: 7, 28]
            // 'pending': Reservó pero no pagó (se libera si pasa el tiempo)
            // 'paid': Turno confirmado (ya puso la plata)
            // 'cancelled': Turno dado de baja
            $table->enum('payment_status', ['pending', 'paid', 'cancelled'])->default('pending');
            
            // Datos de la Transacción
            $table->decimal('total_amount', 10, 2); // Guardamos el precio al momento de reservar
            $table->string('mercadopago_id')->nullable(); // ID de la transacción de MP para reclamos
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('appointments');
    }
};