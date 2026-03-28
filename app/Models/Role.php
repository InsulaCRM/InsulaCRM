<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    protected $fillable = ['name', 'display_name', 'is_system', 'tenant_id'];

    protected function casts(): array
    {
        return [
            'is_system' => 'boolean',
        ];
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function permissions()
    {
        return $this->belongsToMany(Permission::class, 'role_permission');
    }

    /**
     * Check if this role has a specific permission.
     */
    public function hasPermission(string $key): bool
    {
        return $this->permissions->contains('key', $key);
    }
}
