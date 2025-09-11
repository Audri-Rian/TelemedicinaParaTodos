<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'appointments_id',
        'event',
        'metadata',
    ];
}


