<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids;

    protected $table = 'employee';
    public $timestamps = false;
    protected $fillable = ['name', 'job_title', 'specialty', 'phone'];
    protected $casts = ['created_at' => 'datetime'];

    public function account()
    {
        return $this->hasOne(Account::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }

    public function availability()
    {
        return $this->hasMany(DoctorAvailability::class, 'doctor_id');
    }

    public function timeOff()
    {
        return $this->hasMany(DoctorTimeOff::class, 'doctor_id');
    }

    // Only doctors keep availability, so callers guard on this before touching it.
    public function isDoctor(): bool
    {
        return $this->job_title === 'doctor';
    }
}
