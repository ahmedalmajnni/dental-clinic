<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;

class Specialty extends Model
{
    use HasUuids;

    protected $table = 'specialty';
    public $timestamps = false;
    protected $fillable = ['name'];
}
