<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'admin_requested_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin_requested_at' => 'datetime',
        ];
    }

    public function documents()
    {
        return $this->hasMany(Document::class, 'created_by');
    }

    public function isAdmin(): bool
    {
        return in_array($this->role, ['admin', 'super_admin'], true);
    }

    // Super admin: level di atas admin biasa. Bisa kelola Kategori & ubah role user lain
    // (naikin jadi admin/super admin, turunin lagi), yang nggak bisa dilakukan admin biasa.
    public function isSuperAdmin(): bool
    {
        return $this->role === 'super_admin';
    }

    // Ada pengajuan "jadi admin" dari user ini yang masih nunggu keputusan
    // Super Admin (belum disetujui atau ditolak)
    public function hasPendingAdminRequest(): bool
    {
        return $this->role === 'user' && $this->admin_requested_at !== null;
    }
}