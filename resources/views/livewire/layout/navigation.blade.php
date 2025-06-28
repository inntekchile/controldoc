<?php

use App\Livewire\Actions\Logout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

new class extends Component
{
    public array $rutasDeListadosUniversales = [
        'gestion.listados.hub', 'gestion.documentos', 'gestion.rubros',
        'gestion.tipos-empresa-legal', 'gestion.nacionalidades',
        'gestion.tipos-condicion-personal', 'gestion.tipos-condicion',
        'gestion.sexos', 'gestion.estados-civiles', 'gestion.etnias',
        'gestion.niveles-educacionales', 'gestion.criterios-evaluacion',
        'gestion.sub-criterios', 'gestion.condiciones-fecha-ingreso',
        'gestion.configuraciones-validacion', 'gestion.textos-rechazo',
        'gestion.aclaraciones-criterio', 'gestion.observaciones-documento',
        'gestion.tipos-carga', 'gestion.tipos-vencimiento',
        'gestion.tipos-entidad-controlable', 'gestion.rangos-cantidad-trabajadores',
        'gestion.mutualidades', 'gestion.regiones', 'gestion.comunas',
        'gestion.formatos-muestra', 'gestion.tipos-vehiculo',
        'gestion.tipos-maquinaria', 'gestion.tipos-embarcacion',
        'gestion.marcas-vehiculo', 'gestion.colores-vehiculo',
        'gestion.tenencias-vehiculo',
    ];

    public function isListadosUniversalesActive(): bool {
        return in_array(Route::currentRouteName(), $this->rutasDeListadosUniversales);
    }
    public function isContratistaPanelActive(string $tab = ''): bool {
        if (!request()->routeIs('contratista.panel')) { return false; }
        if ($tab && request()->query('tab_inicial') === $tab) { return true; }
        if (!$tab && request()->routeIs('contratista.panel') && !request()->query('tab_inicial')) { return true; }
        return false;
    }
    public function logout(Logout $logout): void {
        $logout();
        $this->redirect('/', navigate: true);
    }
}; ?>

<nav x-data="{ open: false }" class="bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" wire:navigate>
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800 dark:text-gray-200" />
                    </a>
                </div>

                <!-- Navigation Links -->
                <div class="hidden space-x-8 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>
                        {{ __('Dashboard') }}
                    </x-nav-link>

                    {{-- Menú para ASEM (Admin y Validador) --}}
                    @if(Auth::user() && Auth::user()->hasAnyRole(['ASEM_Admin', 'ASEM_Validator']))
                        <x-nav-link :href="route('asem.panel-validacion')" :active="request()->routeIs('asem.panel-validacion')" wire:navigate>
                            {{ __('Panel de Validación') }}
                        </x-nav-link>
                    @endif

                    {{-- Menú exclusivo para ASEM_Admin --}}
                    @if(Auth::user() && Auth::user()->hasRole('ASEM_Admin'))
                        <x-nav-link :href="route('gestion.asignacion-documentos')" :active="request()->routeIs('gestion.asignacion-documentos')" wire:navigate>
                            {{ __('Asignar Documentos') }}
                        </x-nav-link>

                        {{-- ============================================= --}}
                        {{-- INICIO: NUEVO ENLACE ESCRITORIO --}}
                        {{-- ============================================= --}}
                        <x-nav-link :href="route('gestion.gestion-general')" :active="request()->routeIs('gestion.gestion-general')" wire:navigate>
                            {{ __('Gestión General') }}
                        </x-nav-link>
                        {{-- ============================================= --}}
                        {{-- FIN: NUEVO ENLACE ESCRITORIO --}}
                        {{-- ============================================= --}}

                        <x-nav-link :href="route('gestion.listados.hub')" :active="$this->isListadosUniversalesActive()" wire:navigate>
                            {{ __('Listados Universales') }}
                        </x-nav-link>
                        <x-nav-link :href="route('gestion.mandantes')" :active="request()->routeIs('gestion.mandantes')" wire:navigate>
                            {{ __('Mandantes') }}
                        </x-nav-link>
                        <x-nav-link :href="route('gestion.contratistas')" :active="request()->routeIs('gestion.contratistas')" wire:navigate>
                            {{ __('Contratistas ASEM') }}
                        </x-nav-link>
                        <x-nav-link :href="route('gestion.reglas-documentales')" :active="request()->routeIs('gestion.reglas-documentales')" wire:navigate>
                            {{ __('Reglas Documentales') }}
                        </x-nav-link>
                        <x-nav-link :href="route('gestion.usuarios')" :active="request()->routeIs('gestion.usuarios')" wire:navigate>
                            {{ __('Usuarios ASEM') }}
                        </x-nav-link>
                    @endif

                    {{-- Menú para Contratista_Admin --}}
                    @if(Auth::user() && Auth::user()->hasRole('Contratista_Admin'))
                        <x-nav-link :href="route('contratista.panel')" :active="$this->isContratistaPanelActive()" wire:navigate>
                            {{ __('Panel Operación') }}
                        </x-nav-link>
                        <x-nav-link :href="route('gestion.documentos.consulta')" :active="request()->routeIs('gestion.documentos.consulta')" wire:navigate>
                            {{ __('Consultar Documentos Req.') }}
                        </x-nav-link>
                    @endif
                </div>
            </div>

             <div class="hidden sm:flex sm:items-center sm:ms-6">
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 dark:text-gray-400 bg-white dark:bg-gray-800 hover:text-gray-700 dark:hover:text-gray-300 focus:outline-none transition ease-in-out duration-150">
                            <div x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" /></svg>
                            </div>
                        </button>
                    </x-slot>
                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile')" wire:navigate>{{ __('Profile') }}</x-dropdown-link>
                        <button wire:click="logout" class="w-full text-start"><x-dropdown-link>{{ __('Log Out') }}</x-dropdown-link></button>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center sm:hidden">
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden">
        <div class="pt-2 pb-3 space-y-1">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</x-responsive-nav-link>

            @if(Auth::user() && Auth::user()->hasAnyRole(['ASEM_Admin', 'ASEM_Validator']))
                <x-responsive-nav-link :href="route('asem.panel-validacion')" :active="request()->routeIs('asem.panel-validacion')" wire:navigate>
                    {{ __('Panel de Validación') }}
                </x-responsive-nav-link>
            @endif
            
            @if(Auth::user() && Auth::user()->hasRole('ASEM_Admin'))
                 <x-responsive-nav-link :href="route('gestion.asignacion-documentos')" :active="request()->routeIs('gestion.asignacion-documentos')" wire:navigate>
                    {{ __('Asignar Documentos') }}
                </x-responsive-nav-link>
                
                {{-- ============================================= --}}
                {{-- INICIO: NUEVO ENLACE RESPONSIVE --}}
                {{-- ============================================= --}}
                <x-responsive-nav-link :href="route('gestion.gestion-general')" :active="request()->routeIs('gestion.gestion-general')" wire:navigate>
                    {{ __('Gestión General') }}
                </x-responsive-nav-link>
                {{-- ============================================= --}}
                {{-- FIN: NUEVO ENLACE RESPONSIVE --}}
                {{-- ============================================= --}}

                <x-responsive-nav-link :href="route('gestion.listados.hub')" :active="$this->isListadosUniversalesActive()" wire:navigate>
                    {{ __('Listados Universales') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('gestion.mandantes')" :active="request()->routeIs('gestion.mandantes')" wire:navigate>
                    {{ __('Mandantes') }}
                </x-responsive-nav-link>
                 <x-responsive-nav-link :href="route('gestion.contratistas')" :active="request()->routeIs('gestion.contratistas')" wire:navigate>
                    {{ __('Contratistas ASEM') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('gestion.reglas-documentales')" :active="request()->routeIs('gestion.reglas-documentales')" wire:navigate>
                    {{ __('Reglas Documentales') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('gestion.usuarios')" :active="request()->routeIs('gestion.usuarios')" wire:navigate>
                    {{ __('Usuarios ASEM') }}
                </x-responsive-nav-link>
            @endif

            @if(Auth::user() && Auth::user()->hasRole('Contratista_Admin'))
                <x-responsive-nav-link :href="route('contratista.panel')" :active="$this->isContratistaPanelActive()" wire:navigate>
                    {{ __('Panel Operación') }}
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('gestion.documentos.consulta')" :active="request()->routeIs('gestion.documentos.consulta')" wire:navigate>
                    {{ __('Consultar Documentos Req.') }}
                </x-responsive-nav-link>
            @endif
        </div>
        <div class="pt-4 pb-1 border-t border-gray-200 dark:border-gray-600">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800 dark:text-gray-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
            </div>
            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile')" wire:navigate>{{ __('Profile') }}</x-responsive-nav-link>
                <button wire:click="logout" class="w-full text-start"><x-responsive-nav-link>{{ __('Log Out') }}</x-responsive-nav-link></button>
            </div>
        </div>
    </div>
</nav>