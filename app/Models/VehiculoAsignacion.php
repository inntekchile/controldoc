<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\Pivot;

class VehiculoAsignacion extends Pivot
{
    protected $table = 'vehiculo_asignaciones';

    // Si tu tabla pivote tiene timestamps, lo cual es una buena práctica.
    public $timestamps = true;

    protected $casts = [
        'is_active' => 'boolean',
        'fecha_asignacion' => 'date',
        'fecha_desasignacion' => 'date',
    ];

    public function vehiculo()
    {
        return $this->belongsTo(Vehiculo::class, 'vehiculo_id');
    }

    public function unidadOrganizacionalMandante()
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }
}