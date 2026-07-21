<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasUuids;

    protected $table = 'appointment';
    public $timestamps = false;
    protected $fillable = ['patient_id', 'doctor_id', 'branch_id', 'scheduled_at', 'status'];
    protected $casts = ['scheduled_at' => 'datetime', 'created_at' => 'datetime'];

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
}
