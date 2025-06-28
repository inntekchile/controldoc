<div>
    {{-- Mensajes Flash --}}
    @if (session()->has('message'))
        <div class="alert-success mb-4">
            {{ session('message') }}
        </div>
    @endif
    @if (session()->has('error'))
        <div class="alert-danger mb-4">
            {{ session('error') }}
        </div>
    @endif
    
    @if ($unidadOrganizacionalId)
        <div class="mb-4 flex flex-col sm:flex-row justify-between items-center">
            <div class="w-full sm:w-2/3 mb-2 sm:mb-0">
                <input wire:model.live.debounce.300ms="searchVehiculo" type="text"
                       placeholder="Buscar por Patente o Marca del Vehículo..."
                       class="input-field w-full">
            </div>
            <button wire:click="abrirModalNuevoVehiculo" class="btn-primary">
                <x-icons.plus class="w-5 h-5 mr-1 inline-block"/> Agregar Nuevo Vehículo
            </button>
        </div>

        <div class="overflow-x-auto shadow-md sm:rounded-lg">
            <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                <thead class="bg-gray-50 dark:bg-gray-700">
                    <tr>
                        <th wire:click="sortBy('id')" class="table-header cursor-pointer">ID</th>
                        <th wire:click="sortBy('patente_letras')" class="table-header cursor-pointer">Patente</th>
                        <th wire:click="sortBy('marca_vehiculo_id')" class="table-header cursor-pointer">Marca / Tipo</th>
                        <th wire:click="sortBy('ano_fabricacion')" class="table-header cursor-pointer">Año Fab.</th>
                        <th class="table-header text-center">% Cumplimiento</th>
                        <th class="table-header text-center">Acceso Habilitado</th>
                        <th wire:click="sortBy('is_active')" class="table-header text-center cursor-pointer">Estado Ficha</th>
                        <th class="table-header text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                    @forelse ($vehiculosPaginados ?? [] as $vehiculo)
                        <tr class="table-row-hover">
                            <td class="table-cell">{{ $vehiculo->id }}</td>
                            <td class="table-cell font-mono">{{ $vehiculo->patente_completa }}</td>
                            <td class="table-cell">
                                <span class="font-semibold">{{ $vehiculo->marcaVehiculo->nombre ?? 'N/A' }}</span>
                                <span class="block text-xs text-gray-500">{{ $vehiculo->tipoVehiculo->nombre ?? 'N/A' }}</span>
                            </td>
                            <td class="table-cell">{{ $vehiculo->ano_fabricacion }}</td>
                            <td class="table-cell text-center text-sm">N/D</td>
                            <td class="table-cell text-center text-sm">N/D</td>
                            <td class="table-cell text-center">
                                <span wire:click="toggleActivoVehiculo({{ $vehiculo->id }})"
                                      class="status-badge {{ $vehiculo->is_active ? 'status-active' : 'status-inactive' }}">
                                    {{ $vehiculo->is_active ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td class="table-cell text-center whitespace-nowrap">
                                <button wire:click="abrirModalEditarVehiculo({{ $vehiculo->id }})" class="action-button-edit" title="Editar Ficha Vehículo"><x-icons.edit class="inline-block"/></button>
                                <button
                                    wire:click="eliminarVehiculo({{ $vehiculo->id }})"
                                    wire:confirm="¿Estás seguro?\n\nEsta acción eliminará permanentemente la ficha del vehículo y todos sus datos asociados. No se puede deshacer."
                                    class="action-button-delete" title="Eliminar Vehículo">
                                    <x-icons.trash class="inline-block"/>
                                </button>
                                <button class="action-button-neutral" title="Ver/Gestionar Documentos (Futuro)">
                                    <x-icons.clipboard-list class="inline-block"/>
                                </button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="table-cell text-center">
                                 No se encontraron vehículos registrados. Puede agregar uno nuevo.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($vehiculosPaginados && $vehiculosPaginados->hasPages())
            <div class="mt-4">
                {{ $vehiculosPaginados->links() }}
            </div>
        @endif
     @else
         <div class="p-4 text-center text-gray-500 dark:text-gray-400">
             Error: El contexto de operación (Mandante - Unidad Organizacional) no está definido. Por favor, regrese al Panel de Operación y seleccione una vinculación.
         </div>
    @endif

    {{-- MODAL: FICHA DEL VEHÍCULO --}}
    @if ($showModalFichaVehiculo)
         <div class="fixed z-10 inset-0 overflow-y-auto" aria-labelledby="modal-title-vehiculo" role="dialog" aria-modal="true">
            <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
             <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity dark:bg-gray-900 dark:bg-opacity-75" aria-hidden="true" wire:click="cerrarModalFichaVehiculo"></div>
             <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">​</span>
             <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-3xl sm:w-full">
                 <form wire:submit.prevent="guardarVehiculo" id="formFichaVehiculo">
                     <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                         <div class="sm:flex sm:items-start w-full">
                             <div class="mt-3 text-center sm:mt-0 sm:text-left w-full">
                                 <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 section-title" id="modal-title-vehiculo">
                                     {{ $vehiculoId ? 'Editar Ficha del Vehículo' : 'Agregar Nuevo Vehículo' }}
                                 </h3>
                                 <div class="mt-4 space-y-4">
                                     <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                                         
                                         <div class="md:col-span-3 section-title !mt-0 !border-none !pb-0">Identificación</div>
                                         <div class="md:col-span-2">
                                             <label class="label-form">Patente <span class="text-red-500">*</span></label>
                                             <div class="flex items-center space-x-2">
                                                <input type="text" wire:model.lazy="patente_letras" id="patente_letras" class="input-field w-1/2 uppercase" placeholder="ABCD">
                                                <span class="text-gray-400">-</span>
                                                <input type="text" wire:model.lazy="patente_numeros" id="patente_numeros" class="input-field w-1/2 uppercase" placeholder="12">
                                             </div>
                                             @error('patente_letras') <span class="error-message">{{ $message }}</span> @enderror
                                             @error('patente_numeros') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>

                                         <div>
                                             <label for="ano_fabricacion" class="label-form">Año de Fabricación <span class="text-red-500">*</span></label>
                                             <input type="number" wire:model.lazy="ano_fabricacion" id="ano_fabricacion" class="input-field w-full" placeholder="YYYY">
                                             @error('ano_fabricacion') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>

                                         <div class="md:col-span-3 section-title !border-none !pb-0">Características</div>

                                         <div>
                                             <label for="marca_vehiculo_id" class="label-form">Marca <span class="text-red-500">*</span></label>
                                             <select wire:model="marca_vehiculo_id" id="marca_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($marcasVehiculo as $marca) <option value="{{ $marca->id }}">{{ $marca->nombre }}</option> @endforeach
                                             </select>
                                             @error('marca_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div>
                                             <label for="color_vehiculo_id" class="label-form">Color <span class="text-red-500">*</span></label>
                                             <select wire:model="color_vehiculo_id" id="color_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($coloresVehiculo as $color) <option value="{{ $color->id }}">{{ $color->nombre }}</option> @endforeach
                                             </select>
                                             @error('color_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         <div>
                                             <label for="tipo_vehiculo_id" class="label-form">Tipo de Vehículo <span class="text-red-500">*</span></label>
                                             <select wire:model="tipo_vehiculo_id" id="tipo_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($tiposVehiculo as $tipo) <option value="{{ $tipo->id }}">{{ $tipo->nombre }}</option> @endforeach
                                             </select>
                                             @error('tipo_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>
                                         
                                         <div class="md:col-span-3">
                                             <label for="tenencia_vehiculo_id" class="label-form">Propiedad del Vehículo (Opcional)</label>
                                             <select wire:model="tenencia_vehiculo_id" id="tenencia_vehiculo_id" class="input-field w-full">
                                                 <option value="">Seleccione...</option>
                                                 @foreach($tenenciasVehiculo as $tenencia) <option value="{{ $tenencia->id }}">{{ $tenencia->nombre }}</option> @endforeach
                                             </select>
                                             @error('tenencia_vehiculo_id') <span class="error-message">{{ $message }}</span> @enderror
                                         </div>

                                         <div class="md:col-span-3">
                                             <label for="vehiculo_is_active_modal" class="label-form flex items-center mt-2">
                                                 <input type="checkbox" wire:model="vehiculo_is_active" id="vehiculo_is_active_modal" class="form-checkbox h-5 w-5 rounded border-gray-300 dark:border-gray-600 text-indigo-600 shadow-sm focus:border-indigo-300 focus:ring focus:ring-indigo-200 focus:ring-opacity-50 dark:bg-gray-700 dark:text-indigo-400">
                                                 <span class="ms-2 text-sm text-gray-600 dark:text-gray-400">Vehículo Activo (disponible para uso)</span>
                                             </label>
                                         </div>

                                     </div>
                                 </div>
                             </div>
                         </div>
                     </div>
                     <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                         <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 sm:ms-3 sm:w-auto sm:text-sm">
                             {{ $vehiculoId ? 'Guardar Cambios' : 'Crear Vehículo' }}
                         </button>
                         <button type="button" wire:click="cerrarModalFichaVehiculo" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 dark:border-gray-600 shadow-sm px-4 py-2 bg-white dark:bg-gray-800 text-base font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 sm:mt-0 sm:w-auto sm:text-sm">
                             Cancelar
                         </button>
                     </div>
                 </form>
             </div>
         </div>
        </div>
    @endif
</div>