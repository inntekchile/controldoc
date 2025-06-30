<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;
use Illuminate\Support\Facades\Storage; // <-- Importación necesaria

class DocumentoCargado extends Model
{
    use HasFactory;

    protected $table = 'documentos_cargados';

    // Se usa guarded en lugar de fillable para permitir todos los campos de forma segura
    // ya que estamos controlando la creación en el componente Livewire.
    protected $guarded = ['id'];

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

    // ======================================================================================
    // INICIO DE LA CORRECCIÓN
    // Se añade un nuevo "Accessor" para generar una URL segura a nuestra ruta controlada.
    // ======================================================================================
    protected function url(): Attribute
    {
        return Attribute::make(
            get: function () {
                if ($this->ruta_archivo) {
                    // Genera una URL a la ruta que hemos nombrado 'archivo.publico'
                    return route('archivo.publico', ['filePath' => $this->ruta_archivo]);
                }
                return '#'; // Devuelve un enlace no funcional si no hay archivo
            }
        );
    }
    // ======================================================================================
    // FIN DE LA CORRECCIÓN
    // ======================================================================================
    
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
        // Asumiendo que tu modelo de usuario es App\Models\User
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
                if (is_null($this->fecha_vencimiento)) {
                    return 'Por Periodo';
                }
                if (Carbon::parse($this->fecha_vencimiento)->endOfDay()->isPast()) {
                    return 'Vencido';
                }
                return 'Vigente';
            }
        );
    }

    // Evento para borrar el archivo físico al eliminar el registro
    protected static function boot()
    {
        parent::boot();
        static::deleting(function ($documento) {
            if ($documento->ruta_archivo) {
                Storage::disk('public')->delete($documento->ruta_archivo);
            }
        });
    }
}