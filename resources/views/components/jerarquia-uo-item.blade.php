@props([
    'uo',
    'level' => 0,
    'selectedUOsConCondicion' => [],
    'tiposCondicionDisponibles' => []
])

@php
    $currentUoId = (int) $uo->id; // Castear el ID de la UO actual a entero

    // Verificar si la UO actual está seleccionada (es decir, si su ID existe como clave en $selectedUOsConCondicion)
    // Es importante asegurarse de que las claves en $selectedUOsConCondicion también se traten como enteros si es necesario,
    // aunque array_key_exists suele manejar bien claves numéricas como strings si el array fue indexado con enteros.
    // Para mayor seguridad, podríamos castear las claves del array al comparar, pero probemos así primero.
    // Si $selectedUnidadesConCondicion se llena desde el modelo donde los IDs son enteros, esto debería funcionar.
    $isChecked = array_key_exists($currentUoId, $selectedUOsConCondicion);

    // Obtener la condición seleccionada para esta UO si está marcada.
    $condicionSeleccionadaParaEstaUO = null;
    if ($isChecked) {
        // Accedemos usando $currentUoId que es un entero, asumiendo que las claves de $selectedUOsConCondicion son también enteras
        // o que PHP las manejará correctamente.
        $condicionSeleccionadaParaEstaUO = $selectedUOsConCondicion[$currentUoId] ?? null;
    }

    $checkboxId = 'uo_checkbox_' . $currentUoId;
    $selectId = 'uo_condition_' . $currentUoId;
@endphp

<div style="margin-left: {{ $level * 20 }}px;" class="py-1">
    <div class="flex items-center space-x-3">
        <input type="checkbox"
               id="{{ $checkboxId }}"
               value="{{ $currentUoId }}" {{-- El valor del checkbox es el ID de la UO --}}
               wire:change="toggleUOCondicion({{ $currentUoId }}, $event.target.checked)"
               @if($isChecked) checked @endif
               class="form-checkbox h-4 w-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500"
               wire:key="checkbox-{{ $currentUoId }}"
        >

        <label for="{{ $checkboxId }}" class="flex-grow text-sm text-gray-700 dark:text-gray-300 cursor-pointer">
            {{ $uo->nombre_unidad }}
            <small class="text-gray-500 dark:text-gray-400">({{ $uo->codigo_unidad ?? 'Sin código' }})</small>
        </label>

        @if($isChecked) {{-- Solo mostrar el select si la UO está marcada (checkbox activo) --}}
            <select id="{{ $selectId }}"
                    wire:model.defer="selectedUnidadesConCondicion.{{ $currentUoId }}" {{-- El modelo es la entrada del array con clave $currentUoId --}}
                    wire:key="select-condition-{{ $currentUoId }}"
                    class="input-field text-xs py-1 px-2 w-auto sm:w-48"
                    style="min-width: 150px;"
                    title="Condición para {{ $uo->nombre_unidad }}">
                <option value="">-- Sin Condición --</option>
                @foreach ($tiposCondicionDisponibles as $tipoCondicion)
                    <option value="{{ $tipoCondicion->id }}"
                        {{-- Para preseleccionar el valor correcto en el select.
                             Es importante que $condicionSeleccionadaParaEstaUO y $tipoCondicion->id sean del mismo tipo
                             o que la comparación no estricta (==) funcione.
                             Si $condicionSeleccionadaParaEstaUO es null, ninguna opción (excepto "-- Sin Condición --")
                             se marcará como 'selected' por esta lógica, lo cual es correcto.
                        --}}
                        @if( (string) $condicionSeleccionadaParaEstaUO == (string) $tipoCondicion->id ) selected @endif
                    >
                        {{ $tipoCondicion->nombre }}
                    </option>
                @endforeach
            </select>
        @else
            {{-- Placeholder visual si la UO no está marcada --}}
            <div class="input-field text-xs py-1 px-2 w-auto sm:w-48 text-gray-400 italic" style="min-width: 150px; border-style: dashed; background: transparent; opacity: 0.6;">(Asignar UO)</div>
        @endif
    </div>
</div>

{{-- Renderizado recursivo para UOs hijas --}}
@if ($uo->children_uos && $uo->children_uos->isNotEmpty())
    @foreach ($uo->children_uos as $childUo)
        <x-jerarquia-uo-item
            :uo="$childUo"
            :level="$level + 1"
            :selectedUOsConCondicion="$selectedUOsConCondicion"
            :tiposCondicionDisponibles="$tiposCondicionDisponibles"
            wire:key="jerarquia-item-{{ $childUo->id }}-level-{{ $level + 1 }}" {{-- Key más específica para recursividad --}}
        />
    @endforeach
@endif