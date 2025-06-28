<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // ¡Asegúrate de que esta línea esté presente!

class Embarcacion extends Model
{
    use HasFactory;

    protected $table = 'embarcaciones';

    protected $fillable = [
        'contratista_id',
        'matricula_letras',
        'matricula_numeros',
        'ano_fabricacion',
        'tipo_embarcacion_id',
        'tenencia_vehiculo_id', // Reutilizamos este campo
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // --- INICIO BLOQUE NUEVO ---
    /**
     * El "booted" method del modelo.
     * Esto asegura que cuando se elimine una embarcación, todas sus asignaciones también se eliminen.
     */
    protected static function booted(): void
    {
        static::deleting(function (Embarcacion $embarcacion) {
            $embarcacion->unidadesOrganizacionales()->detach();
        });
    }

    /**
     * Las unidades organizacionales a las que esta embarcación está asignada.
     */
    public function unidadesOrganizacionales(): BelongsToMany
    {
        return $this->belongsToMany(
                UnidadOrganizacionalMandante::class,
                'embarcacion_asignaciones', // Nombre de la tabla pivote
                'embarcacion_id',
                'unidad_organizacional_mandante_id'
            )
            ->using(EmbarcacionAsignacion::class) // Modelo pivote personalizado
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }
    // --- FIN BLOQUE NUEVO ---

    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    public function tipoEmbarcacion(): BelongsTo
    {
        return $this->belongsTo(TipoEmbarcacion::class, 'tipo_embarcacion_id');
    }

    public function tenencia(): BelongsTo
    {
        return $this->belongsTo(TenenciaVehiculo::class, 'tenencia_vehiculo_id');
    }

    public function getMatriculaCompletaAttribute(): string
    {
        return strtoupper($this->matricula_letras) . ' - ' . $this->matricula_numeros;
    }
}