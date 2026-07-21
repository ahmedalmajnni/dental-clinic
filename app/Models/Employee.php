<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Employee extends Model
{
    use HasUuids;

    protected $table = 'employee';
    public $timestamps = false;
    protected $fillable = ['branch_id', 'name', 'job_title', 'phone'];
    protected $casts = ['created_at' => 'datetime'];

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }

    public function account()
    {
        return $this->hasOne(Account::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'doctor_id');
    }
}
