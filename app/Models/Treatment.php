<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Treatment extends Model
{
    use HasUuids;

    protected $table = 'treatment';
    public $timestamps = false;
    protected $fillable = ['appointment_id', 'patient_id', 'procedure', 'cost', 'status'];
    protected $casts = ['cost' => 'decimal:2', 'created_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function report()
    {
        return $this->hasOne(Report::class, 'appointment_id', 'appointment_id');
    }

    public function invoiceLine()
    {
        return $this->hasOne(InvoiceLine::class);
    }
}
