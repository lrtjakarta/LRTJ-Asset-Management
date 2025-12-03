<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'name',
        'email',
        'password',
        'kode_department', // internal only (not from LDAP)
        'ou',              // just informational from LDAP
        'role_kode',       // primary role for UI / filtering
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_recovery_codes',
        'two_factor_secret',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    public function roles()
    {
        return $this->belongsToMany(
            MasterRole::class, // related model
            'user_role',       // pivot table
            'user_id',         // foreignPivotKey  (column on pivot that references users.id)
            'role_kode',       // relatedPivotKey  (column on pivot that references master_role.kode)
            'id',              // parentKey        (local key on users)
            'kode'             // relatedKey       (local key on master_role)
        )->withTimestamps();
    }
    public function department()
    {
        return $this->belongsTo(MasterUserCode::class, 'kode_department', 'kode');
    }

    public function hasAction(string $menuKode, string $action): bool
    {
        $roleKodes = $this->roles()
            ->pluck('kode')
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (empty($roleKodes)) {
            return false;
        }

        return MasterRoleMenu::query()
            ->where('menu_kode', $menuKode)
            ->whereIn('role_kode', $roleKodes)
            ->whereJsonContains('actions', $action)
            ->exists();
    }
}
