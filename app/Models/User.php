<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    public const ADMIN = 'admin';
    public const SUPER_ADMIN = 'super_admin';
    public const USER = 'user';
    public const MEMBER = 'user';

    public const ROLE_ADMIN = self::ADMIN;
    public const ROLE_SUPER_ADMIN = self::SUPER_ADMIN;
    public const ROLE_USER = self::USER;
    public const ROLE_MEMBER = self::MEMBER;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'email',
        'password',
        'role',
        'avatar_url',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function gmailAccounts()
    {
        return $this->hasMany(GmailAccount::class);
    }

    public function hasRole(string|array $roles): bool
    {
        $roles = (array) $roles;

        if ($this->role === self::SUPER_ADMIN) {
            return true;
        }

        return in_array($this->role, $roles, true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->role === self::SUPER_ADMIN;
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, [self::SUPER_ADMIN, self::ADMIN], true);
    }

    public function isTenantAdmin(): bool
    {
        return $this->role === self::ADMIN;
    }

    public function canViewOrganizationLeads(): bool
    {
        return $this->isSuperAdmin() || $this->isTenantAdmin();
    }

    public function getRoleBadgeClass(): string
    {
        return match ($this->role) {
            'super_admin' => 'bg-label-danger',
            'admin' => 'bg-label-primary',
            default => 'bg-label-info',
        };
    }

    public function getRoleLabel(): string
    {
        return match ($this->role) {
            'super_admin' => 'Super Admin',
            'admin' => 'Tenant Admin',
            default => 'Team Member',
        };
    }
}
