<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany; // Importar BelongsToMany

class Vehiculo extends Model
{
    use HasFactory;

    protected $table = 'vehiculos';

    protected $fillable = [
        'contratista_id',
        'patente_letras',
        'patente_numeros',
        'ano_fabricacion',
        'marca_vehiculo_id',
        'color_vehiculo_id',
        'tipo_vehiculo_id',
        'tenencia_vehiculo_id',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // --- INICIO BLOQUE NUEVO: ELIMINACIÓN EN CASCADA PARA ASIGNACIONES ---
    protected static function booted(): void
    {
        static::deleting(function (Vehiculo $vehiculo) {
            $vehiculo->unidadesOrganizacionales()->detach();
        });
    }
    // --- FIN BLOQUE NUEVO ---

    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    public function marcaVehiculo(): BelongsTo
    {
        return $this->belongsTo(MarcaVehiculo::class, 'marca_vehiculo_id');
    }

    public function colorVehiculo(): BelongsTo
    {
        return $this->belongsTo(ColorVehiculo::class, 'color_vehiculo_id');
    }

    public function tipoVehiculo(): BelongsTo
    {
        return $this->belongsTo(TipoVehiculo::class, 'tipo_vehiculo_id');
    }

    public function tenenciaVehiculo(): BelongsTo
    {
        return $this->belongsTo(TenenciaVehiculo::class, 'tenencia_vehiculo_id');
    }
    
    // --- INICIO NUEVA RELACIÓN ---
    public function unidadesOrganizacionales(): BelongsToMany
    {
        return $this->belongsToMany(
                UnidadOrganizacionalMandante::class,
                'vehiculo_asignaciones', // Nombre de la tabla pivote
                'vehiculo_id',
                'unidad_organizacional_mandante_id'
            )
            ->using(VehiculoAsignacion::class) // Modelo pivote personalizado
            ->withPivot(['id', 'fecha_asignacion', 'fecha_desasignacion', 'is_active', 'motivo_desasignacion'])
            ->withTimestamps();
    }
    // --- FIN NUEVA RELACIÓN ---

    public function getPatenteCompletaAttribute(): string
    {
        return strtoupper($this->patente_letras) . ' • ' . $this->patente_numeros;
    }
}