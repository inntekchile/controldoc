<div>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('Gestión de Usuarios') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg p-6">
                
                @if (session()->has('message')) <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert"><p>{{ session('message') }}</p></div> @endif
                @if (session()->has('error')) <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert"><p>{{ session('error') }}</p></div> @endif

                <!-- ============== SECCIÓN DE FILTROS (CORREGIDA) ============== -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
                    <input wire:model.debounce.500ms="search" type="text" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300" placeholder="Buscar por nombre...">
                    
                    <input wire:model.debounce.500ms="filtroEmail" type="text" class="form-input rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300" placeholder="Buscar por email...">

                    <!-- Se añade .live para que el filtro se aplique al instante -->
                    <select wire:model.live="filtroRol" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        <option value="">-- Todos los Roles --</option>
                        @foreach($roles as $role)
                            <option value="{{ $role->name }}">{{ $role->name }}</option>
                        @endforeach
                    </select>

                    <!-- Se añade .live para que el filtro se aplique al instante -->
                    <select wire:model.live="filtroEstado" class="form-select rounded-md shadow-sm dark:bg-gray-700 dark:text-gray-300">
                        <option value="">-- Todos los Estados --</option>
                        <option value="1">Activo</option>
                        <option value="0">Inactivo</option>
                    </select>
                </div>
                <!-- ========================================================= -->

                <div class="flex justify-end mb-4">
                    <x-primary-button wire:click="create()">
                        Crear Nuevo Usuario
                    </x-primary-button>
                </div>

                <!-- Tabla de Usuarios -->
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-600">
                        <thead class="bg-gray-50 dark:bg-gray-700">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Nombre</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Email</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rol</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Estado</th>
                                <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-600">
                            @forelse ($users as $user)
                            <tr wire:key="user-{{ $user->id }}">
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->email }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">{{ $user->roles->first()->name ?? 'Sin Rol' }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm"><span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $user->is_active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <button wire:click="edit({{ $user->id }})" class="text-blue-600 hover:text-blue-900 mr-4">Editar</button>
                                    <button wire:click="toggleStatus({{ $user->id }})" class="text-indigo-600 hover:text-indigo-900 mr-4">{{ $user->is_active ? 'Desactivar' : 'Activar' }}</button>
                                    <button wire:click="confirmUserDeletion({{ $user->id }})" class="text-red-600 hover:text-red-900">Eliminar</button>
                                </td>
                            </tr>
                            @empty
                            <tr><td colspan="5" class="px-6 py-4 text-center">No se encontraron usuarios que coincidan con los filtros.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">{{ $users->links() }}</div>
            </div>
        </div>
    </div>

    <!-- Modal para Crear/Editar Usuario (sin cambios) -->
    @if ($isModalOpen)
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity"><div class="absolute inset-0 bg-gray-500 opacity-75"></div></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <form wire:submit.prevent="save">
                    <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                        <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100 mb-4">{{ $userId ? 'Editar Usuario' : 'Crear Nuevo Usuario' }}</h3>
                        <div class="mb-4">
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Nombre</label>
                            <input type="text" wire:model.defer="name" id="name" class="mt-1 form-input block w-full"> @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Email</label>
                            <input type="email" wire:model.defer="email" id="email" class="mt-1 form-input block w-full"> @error('email') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="selectedRole" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Rol</label>
                            <select wire:model.live="selectedRole" id="selectedRole" class="mt-1 form-select block w-full">
                                <option value="">Seleccione un rol...</option>
                                @foreach($roles as $role)
                                    <option value="{{ $role->name }}">{{ $role->name }}</option>
                                @endforeach
                            </select> @error('selectedRole') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @if (str_contains(strtolower($selectedRole ?? ''), 'contratista'))
                        <div class="mb-4 animate-fade-in">
                            <label for="contratista_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asociar a Contratista</label>
                            <select wire:model.defer="contratista_id" id="contratista_id" class="mt-1 form-select block w-full">
                                <option value="">Seleccione un contratista...</option>
                                @foreach($contratistas as $contratista)
                                    <option value="{{ $contratista->id }}">{{ $contratista->razon_social }}</option>
                                @endforeach
                            </select> @error('contratista_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        @if (str_contains(strtolower($selectedRole ?? ''), 'mandante'))
                        <div class="mb-4 animate-fade-in">
                            <label for="mandante_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Asociar a Mandante</label>
                            <select wire:model.defer="mandante_id" id="mandante_id" class="mt-1 form-select block w-full">
                                <option value="">Seleccione un mandante...</option>
                                @foreach($mandantes as $mandante)
                                    <option value="{{ $mandante->id }}">{{ $mandante->razon_social }}</option>
                                @endforeach
                            </select> @error('mandante_id') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        @endif
                        <div class="mb-4">
                            <label for="password" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Contraseña</label>
                            <input type="password" wire:model.defer="password" id="password" class="mt-1 form-input block w-full" placeholder="Dejar en blanco para no cambiar">
                            @error('password') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="mb-4">
                            <label for="password_confirmation" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Confirmar Contraseña</label>
                            <input type="password" wire:model.defer="password_confirmation" id="password_confirmation" class="mt-1 form-input block w-full">
                        </div>
                    </div>
                    <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                        <button type="submit" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-blue-600 text-base font-medium text-white hover:bg-blue-700 sm:ml-3 sm:w-auto sm:text-sm">Guardar</button>
                        <button type="button" wire:click="closeModal()" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif
    
    <!-- Modal de confirmación de borrado (sin cambios) -->
    @if ($confirmingUserDeletionId)
    <div class="fixed z-10 inset-0 overflow-y-auto">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 transition-opacity"><div class="absolute inset-0 bg-gray-500 opacity-75"></div></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen">​</span>
            <div class="inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-white dark:bg-gray-800 px-4 pt-5 pb-4 sm:p-6 sm:pb-4">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-12 w-12 rounded-full bg-red-100 sm:mx-0 sm:h-10 sm:w-10">
                            <svg class="h-6 w-6 text-red-600" stroke="currentColor" fill="none" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>
                        </div>
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left">
                            <h3 class="text-lg leading-6 font-medium text-gray-900 dark:text-gray-100">Eliminar Usuario</h3>
                            <div class="mt-2"><p class="text-sm text-gray-500 dark:text-gray-400">¿Estás seguro de que deseas eliminar este usuario? Esta acción no se puede deshacer.</p></div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-50 dark:bg-gray-700 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <button wire:click="deleteUser()" type="button" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-sm px-4 py-2 bg-red-600 text-base font-medium text-white hover:bg-red-700 sm:ml-3 sm:w-auto sm:text-sm">Eliminar</button>
                    <button wire:click="$set('confirmingUserDeletionId', null)" type="button" class="mt-3 w-full inline-flex justify-center rounded-md border border-gray-300 shadow-sm px-4 py-2 bg-white text-base font-medium text-gray-700 hover:bg-gray-50 sm:mt-0 sm:w-auto sm:text-sm">Cancelar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>