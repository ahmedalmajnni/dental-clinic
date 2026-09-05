<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasUuids;

    protected $table = 'media';
    public $timestamps = false;
    protected $fillable = ['patient_id', 'type', 'category', 'file_url', 'cost', 'taken_at'];
    protected $casts = ['cost' => 'decimal:2', 'taken_at' => 'datetime', 'created_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }
}

