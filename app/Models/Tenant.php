<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tenant extends Model
{
    use HasFactory;

    // Habilitamos la asignación masiva para estos campos
    protected $fillable = [
        'user_id',
        'name',
        'slug',
        'primary_color',
        'logo_url',
    ];

    // Relaciones
    public function services()
    {
        return $this->hasMany(Service::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}