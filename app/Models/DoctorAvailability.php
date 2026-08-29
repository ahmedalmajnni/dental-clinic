<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

/**
 * One weekly recurring working window for a doctor, e.g. Monday 09:00-13:00 in
 * 30 minute slots. The bookable slots themselves are generated from these rows
 * rather than stored.
 */
class DoctorAvailability extends Model
{
    use HasUuids;

    protected $table = 'doctor_availability';
    public $timestamps = false;

    protected $fillable = ['doctor_id', 'weekday', 'start_time', 'end_time', 'slot_minutes'];

    // start_time/end_time stay raw strings ("09:00:00"); casting a TIME column
    // to datetime would invent a date and break the slot arithmetic.
    protected $casts = [
        'weekday' => 'integer',
        'slot_minutes' => 'integer',
        'created_at' => 'datetime',
    ];

    public function doctor()
    {
        return $this->belongsTo(Employee::class, 'doctor_id');
    }
}
