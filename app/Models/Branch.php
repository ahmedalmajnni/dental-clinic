<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Branch extends Model
{
    use HasUuids;

    protected $table = 'branch';
    public $timestamps = false;
    protected $fillable = ['name', 'type', 'phone', 'address'];
    protected $casts = ['created_at' => 'datetime'];

    public function employees()
    {
        return $this->hasMany(Employee::class);
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }
}
