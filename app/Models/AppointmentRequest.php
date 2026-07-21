<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class AppointmentRequest extends Model
{
    use HasUuids;

    protected $table = 'appointment_request';
    public $timestamps = false;

    protected $fillable = [
        'patient_id', 'doctor_id', 'branch_id', 'preferred_date', 'note',
        'status', 'appointment_id', 'response_note', 'processed_by', 'processed_at',
    ];

    protected $casts = [
        'preferred_date' => 'date',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
