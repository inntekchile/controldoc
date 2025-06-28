<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrabajadorVinculacion extends Model
{
    use HasFactory;

    protected $table = 'trabajador_vinculaciones';

    protected $fillable = [
        'trabajador_id',
        'unidad_organizacional_mandante_id',
        'cargo_mandante_id',
        'tipo_condicion_personal_id', // Asegúrate que este campo existe en tu migración
        'fecha_ingreso_vinculacion',
        'fecha_contrato',
        'is_active',
        'fecha_desactivacion',
        'motivo_desactivacion',
    ];

    protected $casts = [
        'fecha_ingreso_vinculacion' => 'date',
        'fecha_contrato' => 'date',
        'is_active' => 'boolean',
        'fecha_desactivacion' => 'date',
    ];

    public function trabajador(): BelongsTo
    {
        return $this->belongsTo(Trabajador::class);
    }

    public function unidadOrganizacionalMandante(): BelongsTo
    {
        return $this->belongsTo(UnidadOrganizacionalMandante::class, 'unidad_organizacional_mandante_id');
    }

    public function cargoMandante(): BelongsTo
    {
        return $this->belongsTo(CargoMandante::class, 'cargo_mandante_id');
    }

    public function tipoCondicionPersonal(): BelongsTo
    {
        // Asegúrate que el modelo TipoCondicionPersonal existe y la FK es correcta
        return $this->belongsTo(TipoCondicionPersonal::class, 'tipo_condicion_personal_id');
    }
}