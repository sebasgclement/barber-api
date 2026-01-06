<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'service_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'scheduled_at',
        'ends_at',
        'payment_status', // Fundamental para el filtro de pagados [cite: 28]
        'total_amount',
        'mercadopago_id',
    ];

    // Laravel castea automáticamente la fecha a un objeto Carbon (muy útil para formatear)
    protected $casts = [
        'scheduled_at' => 'datetime',
    ];

    // Relaciones
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }
}