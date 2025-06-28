<?php

namespace App\Livewire\Asem;

use Livewire\Component;
use App\Models\DocumentoCargado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Foundation\Auth\Access\AuthorizationException;
use Carbon\Carbon; // <--- IMPORTACIÓN AÑADIDA

class RevisarDocumento extends Component
{
    public ?DocumentoCargado $documento = null;
    public ?DocumentoCargado $documentoRelacionado = null;
    public $pdfUrl;

    public $decision = null;
    public $isReadOnly = false;
    public $criteriosCumplidos = [];
    
    // ===============================================================
    // INICIO: PROPIEDADES NUEVAS Y MODIFICADAS PARA FECHAS
    // ===============================================================
    public $fechaVencimientoValidador;
    public $confirmaFechaVencimiento = false;

    public $fechaEmisionValidador; // <-- NUEVA
    public $confirmaFechaEmision = false; // <-- NUEVA
    // ===============================================================
    // FIN: PROPIEDADES NUEVAS Y MODIFICADAS
    // ===============================================================
    
    public $motivoDevolucion;
    public $motivosRechazoCalculados = [];

    public function mount($documentoId)
    {
        $documento = DocumentoCargado::with([
            'mandante', 
            'contratista', 
            'entidad', 
            'reglaDocumental.formatoDocumento'
        ])->find($documentoId);
        
        if (!$documento) {
            session()->flash('error', 'El documento solicitado no existe.');
            return $this->redirect(route('asem.panel-validacion'));
        }

        if ($documento->asem_validador_id !== Auth::id() && $documento->estado_validacion === 'Pendiente') {
            throw new AuthorizationException('No tienes permiso para revisar este documento.');
        }

        if ($documento->estado_validacion !== 'Pendiente') {
            session()->flash('info', 'Este documento ya ha sido procesado. Se muestra en modo de solo lectura.');
            $this->isReadOnly = true;
        }

        $this->documento = $documento;
        $this->pdfUrl = Storage::disk('public')->exists($this->documento->ruta_archivo)
                        ? Storage::disk('public')->url($this->documento->ruta_archivo)
                        : null;

        // ===============================================================
        // INICIO: INICIALIZACIÓN DE AMBAS FECHAS
        // ===============================================================
        $this->fechaVencimientoValidador = $this->documento->fecha_vencimiento?->format('Y-m-d');
        $this->fechaEmisionValidador = $this->documento->fecha_emision?->format('Y-m-d'); // <-- NUEVA
        // ===============================================================
        // FIN: INICIALIZACIÓN DE AMBAS FECHAS
        // ===============================================================

        if ($this->documento->documento_relacionado_id_snapshot) {
             $this->documentoRelacionado = DocumentoCargado::where('entidad_id', $this->documento->entidad_id)
                ->where('entidad_type', $this->documento->entidad_type)
                ->whereHas('reglaDocumental', function ($query) {
                    $query->where('nombre_documento_id', $this->documento->documento_relacionado_id_snapshot);
                })
                ->where('archivado', false)
                ->where('resultado_validacion', 'Aprobado')
                ->latest('created_at')
                ->first();
        }
    }

    public function seleccionarDecision($tipo)
    {
        $this->resetErrorBag();
        $this->decision = $tipo;

        if ($tipo === 'Rechazado') {
            $this->motivosRechazoCalculados = [];
            $criterios = $this->documento->criterios_snapshot ?? [];
            if (!empty($criterios)) {
                foreach ($criterios as $index => $criterio) {
                    if (empty($this->criteriosCumplidos[$index]) && !empty($criterio['texto_rechazo'])) {
                        $this->motivosRechazoCalculados[] = $criterio['texto_rechazo'];
                    } else if (empty($this->criteriosCumplidos[$index])) {
                        $this->motivosRechazoCalculados[] = "No cumple con: " . ($criterio['criterio'] ?? "Criterio " . ($index + 1));
                    }
                }
            }
        }
    }

    public function resetDecision()
    {
        $this->decision = null;
        $this->motivosRechazoCalculados = [];
        $this->resetErrorBag();
    }

    public function procesarDecision()
    {
        if ($this->isReadOnly) return;
        if ($this->decision === 'Aprobado') $this->aprobarDocumento();
        elseif ($this->decision === 'Rechazado') $this->rechazarDocumento();
    }

    private function aprobarDocumento()
    {
        // ===============================================================
        // INICIO: LÓGICA DE VALIDACIÓN PARA AMBAS FECHAS
        // ===============================================================
        $validationRules = [];
        $validationMessages = [];

        if ($this->documento->valida_emision_snapshot) {
            $validationRules['fechaEmisionValidador'] = 'required|date';
            $validationRules['confirmaFechaEmision'] = 'accepted';
            $validationMessages['fechaEmisionValidador.required'] = 'La fecha de emisión es obligatoria.';
            $validationMessages['confirmaFechaEmision.accepted'] = 'Debe confirmar la fecha de emisión.';
        }
        
        if ($this->documento->valida_vencimiento_snapshot) {
            $validationRules['fechaVencimientoValidador'] = 'required|date';
            $validationRules['confirmaFechaVencimiento'] = 'accepted';
            $validationMessages['fechaVencimientoValidador.required'] = 'La fecha de vencimiento es obligatoria.';
            $validationMessages['confirmaFechaVencimiento.accepted'] = 'Debe confirmar la fecha de vencimiento.';
        }

        if (!empty($validationRules)) {
            $this->validate($validationRules, $validationMessages);
        }
        // ===============================================================
        // FIN: LÓGICA DE VALIDACIÓN
        // ===============================================================
        
        $updateData = [
            'estado_validacion' => 'Aprobado',
            'resultado_validacion' => 'Aprobado',
            'fecha_validacion' => now(),
            'observacion_rechazo' => null,
            'motivo_rechazo' => null,
            'fecha_emision' => $this->fechaEmisionValidador, // <-- Guardar fecha de emisión validada
            'fecha_vencimiento' => $this->fechaVencimientoValidador, // <-- Guardar fecha de vencimiento validada
        ];

        // ===============================================================
        // INICIO: RE-CÁLCULO DE FECHA DE VENCIMIENTO SI ES NECESARIO
        // ===============================================================
        if ($this->documento->tipo_vencimiento_snapshot === 'DESDE EMISION' && $this->documento->valida_emision_snapshot) {
            $diasValidez = $this->documento->reglaDocumental->dias_validez_documento ?? 0;
            $updateData['fecha_vencimiento'] = Carbon::parse($this->fechaEmisionValidador)->addDays($diasValidez)->format('Y-m-d');
        }
        // ===============================================================
        // FIN: RE-CÁLCULO
        // ===============================================================

        $this->documento->update($updateData);

        session()->flash('message', 'Documento APROBADO correctamente.');
        return $this->redirect(route('asem.panel-validacion'), navigate: true);
    }

    private function rechazarDocumento()
    {
        if (empty($this->motivosRechazoCalculados)) {
            $this->addError('decision', 'No se puede rechazar sin motivos. Desmarque al menos un criterio.');
            return;
        }

        $motivoFinal = "- " . implode("\n- ", $this->motivosRechazoCalculados);

        $this->documento->update([
            'estado_validacion' => 'Rechazado',
            'resultado_validacion' => 'Rechazado',
            'fecha_validacion' => now(),
            'observacion_rechazo' => "Motivos de rechazo:\n" . $motivoFinal,
            'motivo_rechazo' => json_encode($this->motivosRechazoCalculados),
        ]);

        session()->flash('message', 'Documento RECHAZADO correctamente.');
        return $this->redirect(route('asem.panel-validacion'), navigate: true);
    }

    public function devolverAAdmin()
    {
        if ($this->isReadOnly) return;
        $this->validate(
            ['motivoDevolucion' => 'required|string|min:10'],
            ['motivoDevolucion.required' => 'Debe explicar el motivo.', 'motivoDevolucion.min' => 'El motivo debe tener al menos 10 caracteres.']
        );

        $this->documento->update([
            'asem_validador_id' => null,
            'observacion_interna_asem' => ($this->documento->observacion_interna_asem ? $this->documento->observacion_interna_asem . "\n---\n" : '') . "DEVUELTO POR " . Auth::user()->name . " el " . now()->format('d-m-Y H:i') . ":\n" . $this->motivoDevolucion,
        ]);
        
        session()->flash('message', 'Documento DEVUELTO al panel de asignación.');
        return $this->redirect(route('asem.panel-validacion'), navigate: true);
    }
    
    public function render()
    {
        $criterios = $this->documento ? ($this->documento->criterios_snapshot ?? []) : [];
        $totalCriterios = count($criterios);
        $criteriosMarcados = count(array_filter($this->criteriosCumplidos));

        $puedeAprobar = ($totalCriterios > 0) ? ($totalCriterios === $criteriosMarcados) : true;
        
        // ===============================================================
        // INICIO: VERIFICACIÓN PARA HABILITAR BOTÓN "APROBAR"
        // ===============================================================
        if($this->documento?->valida_vencimiento_snapshot && !$this->confirmaFechaVencimiento) {
            $puedeAprobar = false;
        }
        if($this->documento?->valida_emision_snapshot && !$this->confirmaFechaEmision) { // <-- NUEVA CONDICIÓN
            $puedeAprobar = false;
        }
        // ===============================================================
        // FIN: VERIFICACIÓN
        // ===============================================================
        
        $puedeRechazar = true;
        if ($totalCriterios > 0 && $totalCriterios === $criteriosMarcados) {
            $puedeRechazar = false;
        }

        return view('livewire.asem.revisar-documento', [
            'criterios' => $criterios,
            'puedeAprobar' => $puedeAprobar,
            'puedeRechazar' => $puedeRechazar
        ])->layout('layouts.app');
    }
}