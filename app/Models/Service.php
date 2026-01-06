<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'price',
        'duration_minutes',
        'is_active',
    ];

    // Un servicio pertenece a una barbería
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}