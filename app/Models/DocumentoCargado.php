<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

class DocumentoCargado extends Model
{
    use HasFactory;

    protected $table = 'documentos_cargados';

    protected $fillable = [
        'contratista_id',
        'mandante_id',
        'unidad_organizacional_id',
        'entidad_id',
        'entidad_type',
        'regla_documental_id_origen',
        'usuario_carga_id',
        'ruta_archivo',
        'nombre_original_archivo',
        'mime_type',
        'tamano_archivo',
        'fecha_emision',
        'fecha_vencimiento',
        'periodo',
        'estado_validacion',
        'resultado_validacion',
        'archivado',
        'asem_validador_id',
        'fecha_validacion',
        'observacion_interna_asem',
        'observacion_rechazo',
        'requiere_revalidacion',
        'motivo_revalidacion',
        'nombre_documento_snapshot',
        'tipo_vencimiento_snapshot',
        'valida_emision_snapshot',
        'valida_vencimiento_snapshot',
        'valor_nominal_snapshot',
        'habilita_acceso_snapshot',
        'afecta_cumplimiento_snapshot',
        'es_perseguidor_snapshot',
        'criterios_snapshot',
        'observacion_documento_snapshot',
        'formato_documento_snapshot',
        'documento_relacionado_id_snapshot',
    ];

    protected $casts = [
        'fecha_emision' => 'date',
        'fecha_vencimiento' => 'date',
        'fecha_validacion' => 'datetime',
        'criterios_snapshot' => 'array',
        'archivado' => 'boolean',
        'requiere_revalidacion' => 'boolean',
        'valida_emision_snapshot' => 'boolean',
        'valida_vencimiento_snapshot' => 'boolean',
        'habilita_acceso_snapshot' => 'boolean',
        'afecta_cumplimiento_snapshot' => 'boolean',
        'es_perseguidor_snapshot' => 'boolean',
    ];

    public function entidad()
    {
        return $this->morphTo();
    }

    public function contratista(): BelongsTo
    {
        return $this->belongsTo(Contratista::class, 'contratista_id');
    }

    public function mandante(): BelongsTo
    {
        return $this->belongsTo(Mandante::class, 'mandante_id');
    }

    public function validador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'asem_validador_id');
    }

    public function reglaDocumental(): BelongsTo
    {
        return $this->belongsTo(ReglaDocumental::class, 'regla_documental_id_origen');
    }

    protected function estadoAsignacion(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                if ($this->requiere_revalidacion) {
                    return 'Revalidar';
                }
                
                if (is_null($this->asem_validador_id)) {
                    if ($this->estado_validacion === 'Rechazado') {
                         return 'Devuelto';
                    }
                    return 'Sin Asignar';
                }

                if ($this->resultado_validacion) {
                    return 'Revisado';
                }

                return 'Asignado';
            }
        );
    }

    protected function estadoVigencia(): Attribute
    {
        return Attribute::make(
            get: function ($value) {
                // ==========================================================
                // INICIO: LÓGICA MODIFICADA PARA ESTADO DE VIGENCIA
                // ==========================================================
                if (is_null($this->fecha_vencimiento)) {
                    // Cambiamos "No Aplica" por "Por Periodo"
                    return 'Por Periodo';
                }
                
                if (Carbon::parse($this->fecha_vencimiento)->endOfDay()->isPast()) {
                    return 'Vencido';
                }

                return 'Vigente';
                // ==========================================================
                // FIN: LÓGICA MODIFICADA
                // ==========================================================
            }
        );
    }
}