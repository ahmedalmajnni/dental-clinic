<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasUuids;

    protected $table = 'report';
    public $timestamps = false;
    protected $fillable = ['appointment_id', 'patient_id', 'doctor_id', 'diagnosis', 'notes', 'next_visit'];
    protected $casts = ['next_visit' => 'date', 'created_at' => 'datetime'];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }
}
