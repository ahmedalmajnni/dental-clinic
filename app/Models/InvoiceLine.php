<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class InvoiceLine extends Model
{
    use HasUuids;

    protected $table = 'invoice_line';
    public $timestamps = false; // this table has no created_at at all
    protected $fillable = ['invoice_id', 'treatment_id', 'description', 'amount'];
    protected $casts = ['amount' => 'decimal:2'];

    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }

    public function treatment()
    {
        return $this->belongsTo(Treatment::class);
    }
}
