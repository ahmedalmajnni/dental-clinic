<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    use HasUuids;

    protected $table = 'payment';
    public $timestamps = false;
    protected $fillable = ['patient_id', 'amount', 'method', 'paid_at'];
    protected $casts = ['amount' => 'decimal:2', 'paid_at' => 'datetime', 'created_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
