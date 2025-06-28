<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // ¡Asegúrate de que esta línea esté presente!

class Maquinaria extends Model
{
    use HasFactory;

    protected $table = 'maquinarias';

    protected $fillable = [
        'contratista_id',
        'identificador_letras',
        'identificador_numeros',
        'ano_fabricacion',
        'marca_vehiculo_id',
        'tipo_maquinaria_id',
        'tenencia_vehiculo_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
    
    // --- INICIO BLOQUE NUEVO: Lógica de Modelo ---
    /**
     * El "booted" method del modelo.
     * Esto asegura que cuando se elimine una maquinaria, todas sus asignaciones también se eliminen.
     */
    protected static function booted(): void
    {
        static::deleting(function (Maquinaria $maquinaria) {
            $maquinaria->unidadesOrganizacionales()->detach();
        });
    }

    /**
     * Las unidades organizacionales a las que esta maquinaria está asignada.
     */
    public function unidadesOrganizacionales(): BelongsToMany
    {
        return $this->belongsToMany(
                UnidadOrganizacionalMandante::class,
                'maquinaria_asignaciones', // Nombre de la tabla pivote
                'maquinaria_id',
                'unidad_organizacional_mandante_id'
            )
            ->using(MaquinariaAsignacion::class) // Modelo pivote personalizado
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }
    // --- FIN BLOQUE NUEVO ---

    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    public function tipoMaquinaria(): BelongsTo
    {
        return $this->belongsTo(TipoMaquinaria::class, 'tipo_maquinaria_id');
    }

    public function marca(): BelongsTo
    {
        return $this->belongsTo(MarcaVehiculo::class, 'marca_vehiculo_id');
    }

    public function tenencia(): BelongsTo
    {
        return $this->belongsTo(TenenciaVehiculo::class, 'tenencia_vehiculo_id');
    }

    public function getIdentificadorCompletoAttribute(): string
    {
        return strtoupper($this->identificador_letras) . ' - ' . $this->identificador_numeros;
    }
}