<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class PaymentAllocation extends Model
{
    use HasUuids;

    protected $table = 'payment_allocation';
    public $timestamps = false;
    protected $fillable = ['payment_id', 'invoice_id', 'amount'];
    protected $casts = ['amount' => 'decimal:2', 'created_at' => 'datetime'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
