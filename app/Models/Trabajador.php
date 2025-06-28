<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Trabajador extends Model
{
    use HasFactory;
    // use SoftDeletes;

    // La propiedad $table no es necesaria si el nombre de la tabla es el plural del nombre del modelo en snake_case ('trabajadores').
    // La mantenemos por consistencia si así lo prefieres.
    protected $table = 'trabajadores';

    protected $fillable = [
        'contratista_id',
        'nombres',
        'apellido_paterno',
        'apellido_materno',
        'rut',
        'fecha_nacimiento',
        'sexo_id',
        'nacionalidad_id',
        'email',
        'celular',
        'estado_civil_id',
        'nivel_educacional_id',
        'etnia_id',
        'fecha_ingreso_empresa',
        'direccion_calle',
        'direccion_numero',
        'direccion_departamento',
        'comuna_id',
        'is_active',
    ];

    protected $casts = [
        'fecha_nacimiento' => 'date',
        'fecha_ingreso_empresa' => 'date',
        'is_active' => 'boolean',
    ];

    // --- INICIO BLOQUE NUEVO: ELIMINACIÓN EN CASCADA ---
    /**
     * El "booted" method del modelo.
     * Esto asegura que cuando se elimine un trabajador, todas sus vinculaciones también se eliminen.
     */
    protected static function booted(): void
    {
        static::deleting(function (Trabajador $trabajador) {
            // Eliminar todas las vinculaciones asociadas
            $trabajador->vinculaciones()->delete();
            // Aquí se podría añadir la eliminación de documentos asociados en el futuro.
        });
    }
    // --- FIN BLOQUE NUEVO ---

    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class);
    }

    public function sexo(): BelongsTo
    {
        return $this->belongsTo(Sexo::class);
    }

    public function nacionalidad(): BelongsTo
    {
        return $this->belongsTo(Nacionalidad::class);
    }

    public function estadoCivil(): BelongsTo
    {
        return $this->belongsTo(EstadoCivil::class);
    }

    public function nivelEducacional(): BelongsTo
    {
        return $this->belongsTo(NivelEducacional::class);
    }

    public function etnia(): BelongsTo
    {
        return $this->belongsTo(Etnia::class);
    }

    public function comuna(): BelongsTo
    {
        return $this->belongsTo(Comuna::class);
    }

    public function vinculaciones(): HasMany
    {
        return $this->hasMany(TrabajadorVinculacion::class, 'trabajador_id');
    }

    public function getNombreCompletoAttribute(): string
    {
        return trim("{$this->nombres} {$this->apellido_paterno} {$this->apellido_materno}");
    }

    public function getRegionAttribute()
    {
        if ($this->comuna && $this->comuna->relationLoaded('region')) {
            return $this->comuna->region;
        } elseif ($this->comuna) {
            return $this->comuna->load('region')->region;
        }
        return null;
    }
}