<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Media extends Model
{
    use HasUuids;

    protected $table = 'media';
    public $timestamps = false;
    protected $fillable = ['patient_id', 'branch_id', 'type', 'category', 'file_url', 'taken_at'];
    protected $casts = ['taken_at' => 'datetime', 'created_at' => 'datetime'];

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class);
    }
}
