<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Patient extends Model
{
    use HasUuids;
    use SoftDeletes; // enables "archive" (soft delete) via the deleted_at column

    protected $table = 'patient';
    public $timestamps = false;
    protected $fillable = ['name', 'dob', 'phone', 'email'];
    protected $casts = ['dob' => 'date', 'created_at' => 'datetime'];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function treatments()
    {
        return $this->hasMany(Treatment::class);
    }

    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    public function invoices()
    {
        return $this->hasMany(Invoice::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function labCases()
    {
        return $this->hasMany(LabCase::class);
    }

    public function media()
    {
        return $this->hasMany(Media::class);
    }

    public function appointmentRequests()
    {
        return $this->hasMany(AppointmentRequest::class);
    }

    public function account()
    {
        return $this->hasOne(Account::class);
    }
}
