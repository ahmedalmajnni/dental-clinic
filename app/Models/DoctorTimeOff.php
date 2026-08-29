<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * A date-specific exception to a doctor's weekly hours: kind "off" removes time
 * (the whole day when both times are null, otherwise just that range) and kind
 * "extra" adds a one-off window on top of the weekly ones.
 */
class DoctorTimeOff extends Model
{
    use HasUuids;

    protected $table = 'doctor_time_off';
    public $timestamps = false;

    protected $fillable = [
        'doctor_id', 'on_date', 'kind', 'start_time', 'end_time', 'slot_minutes', 'reason',
    ];

    // start_time/end_time stay raw strings ("09:00:00"); casting a TIME column
    // to datetime would invent a date and break the slot arithmetic.
    protected $casts = [
        'on_date' => 'date',
        'slot_minutes' => 'integer',
        'created_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }
}
