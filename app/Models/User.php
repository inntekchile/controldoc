<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail; // Si vas a usar verificación de email
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens; // Para Sanctum
use Spatie\Permission\Traits\HasRoles; // Importante para Spatie Laravel Permission
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class User extends Authenticatable // Opcionalmente: implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable, HasRoles; // Añadido HasRoles

    /**
     * Los atributos que son asignables en masa.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'user_type',
        'mandante_id',
        'contratista_id',
        'is_platform_admin',
        'is_active',
        'rut',         // <-- AÑADIR ESTA LÍNEA
        'telefono',    // <-- AÑADIR ESTA LÍNEA
        'cargo',       // <-- AÑADIR ESTA LÍNEA
    ];

    /**
     * Los atributos que deben ocultarse para la serialización.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Los atributos que deben ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
        'is_platform_admin' => 'boolean',
        'is_active' => 'boolean',
    ];

    /**
     * Obtener la empresa mandante a la que pertenece el usuario (si aplica).
     */
    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class);
    }

    /**
     * Obtener la empresa contratista a la que pertenece el usuario (si aplica).
     */
    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    // Métodos auxiliares para verificar el tipo de usuario de forma más legible
    public function isAsem(): bool
    {
        return $this->user_type === 'asem';
    }

    public function isMandante(): bool
    {
        return $this->user_type === 'mandante';
    }

    public function isContratista(): bool
    {
        return $this->user_type === 'contratista';
    }
}