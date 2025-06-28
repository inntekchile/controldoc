<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importación para la nueva relación
use App\Models\TipoEntidadControlable; // Importación del modelo relacionado

class Mandante extends Model
{
    use HasFactory;

    protected $table = 'mandantes'; // Especificar explícitamente el nombre de la tabla

    protected $fillable = [
        'razon_social',
        'rut',
        'persona_contacto_nombre',
        'persona_contacto_email',
        'persona_contacto_telefono',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class); // Asume FK 'mandante_id' en tabla users si hay relación directa
    }

    public function unidadesOrganizacionales(): HasMany
    {
        // Si la clave foránea en la tabla 'unidad_organizacional_mandantes' es 'mandante_id' (estándar)
        // no es estrictamente necesario especificarla aquí, pero es buena práctica.
        return $this->hasMany(UnidadOrganizacionalMandante::class, 'mandante_id');
    }

    // NUEVA RELACIÓN
    public function cargosDefinidos(): HasMany
    {
        // Si la clave foránea en la tabla 'cargo_mandantes' es 'mandante_id' (estándar)
        // no es estrictamente necesario especificarla aquí, pero es buena práctica.
        return $this->hasMany(CargoMandante::class, 'mandante_id');
    }

    /**
     * Los tipos de entidad controlable que este mandante permite gestionar.
     */
    public function tiposEntidadControlable(): BelongsToMany
    {
        return $this->belongsToMany(
            TipoEntidadControlable::class,
            'mandante_tipo_entidad',        // Nombre de la tabla pivote
            'mandante_id',                  // Clave foránea de este modelo (Mandante) en la tabla pivote
            'tipo_entidad_controlable_id'   // Clave foránea del modelo relacionado (TipoEntidadControlable) en la tabla pivote
        );
        // No se usa withTimestamps() porque la tabla pivote 'mandante_tipo_entidad' no tiene columnas de timestamp.
    }
}