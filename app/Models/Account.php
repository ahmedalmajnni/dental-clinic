<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * The single login table for everyone (admin / employee / patient).
 * Extends Laravel's Authenticatable so it can be the auth user, but points at
 * our "account" table and uses "password_hash" instead of the default "password".
 */
class Account extends Authenticatable
{
    use HasUuids;

    protected $table = 'account';
    public $timestamps = false; // schema has created_at only, no updated_at

    protected $fillable = ['email', 'password_hash', 'role', 'employee_id', 'patient_id', 'is_active'];
    protected $hidden = ['password_hash'];
    protected $casts = ['is_active' => 'boolean', 'last_login' => 'datetime', 'created_at' => 'datetime'];

    // Tell Laravel's auth which column holds the hashed password.
    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class)->withTrashed();
    }

    // A friendly display name, taken from whichever owner this account has.
    public function getNameAttribute(): ?string
    {
        return $this->employee?->name ?? $this->patient?->name;
    }

    /**
     * Who may see every appointment request: the manager (admin) and reception,
     * since reception schedules for the whole clinic. Doctors only ever see the
     * requests booked with them.
     */
    public function seesAllRequests(): bool
    {
        return $this->role === 'admin' || $this->employee?->job_title === 'reception';
    }
}
