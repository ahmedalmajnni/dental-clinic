<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class LabCase extends Model
{
    use HasUuids;

    protected $table = 'lab_case';
    public $timestamps = false;
    protected $fillable = ['patient_id', 'doctor_id', 'type', 'due_date', 'status', 'cost'];
    protected $casts = ['due_date' => 'date', 'cost' => 'decimal:2', 'created_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }
}
