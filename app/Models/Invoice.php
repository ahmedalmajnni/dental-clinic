<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Invoice extends Model
{
    use HasUuids;

    protected $table = 'invoice';
    public $timestamps = false;
    protected $fillable = ['patient_id', 'total', 'balance', 'status'];
    protected $casts = ['total' => 'decimal:2', 'balance' => 'decimal:2', 'created_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function lines()
    {
        return $this->hasMany(InvoiceLine::class);
    }

    public function allocations()
    {
        return $this->hasMany(PaymentAllocation::class);
    }
}
