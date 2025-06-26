<?php

use Illuminate\Support\Facades\Route;

// --- Importaciones de Componentes para ASEM_Admin ---
use App\Livewire\GestionListadosUniversalesHub;
// Catálogos Universales
use App\Livewire\ListarNombreDocumentos; // Asumiendo que es el nombre correcto del componente para 'documentos'
use App\Livewire\GestionRubros;
use App\Livewire\GestionTiposEmpresaLegal;
use App\Livewire\GestionNacionalidades;
use App\Livewire\GestionTiposCondicionPersonal;
use App\Livewire\GestionSexos;
use App\Livewire\GestionEstadosCiviles;
use App\Livewire\GestionEtnias;
use App\Livewire\GestionNivelesEducacionales;
use App\Livewire\GestionCriteriosEvaluacion;
use App\Livewire\GestionSubCriterios;
use App\Livewire\GestionCondicionesFechaIngreso;
use App\Livewire\GestionConfiguracionesValidacion;
use App\Livewire\GestionTextosRechazo;
use App\Livewire\GestionAclaracionesCriterio;
use App\Livewire\GestionObservacionesDocumento;
use App\Livewire\GestionTiposCarga;
use App\Livewire\GestionTiposVencimiento;
use App\Livewire\GestionTiposEntidadControlable;
use App\Livewire\GestionTiposCondicion; // Para empresas
use App\Livewire\GestionRangosCantidadTrabajadores;
use App\Livewire\GestionMutualidades;
use App\Livewire\GestionRegiones;
use App\Livewire\GestionComunas;
use App\Livewire\GestionFormatosMuestra;
// Gestión Principal de Entidades por ASEM
use App\Livewire\GestionMandantes;
use App\Livewire\GestionUnidadesOrganizacionalesMandante;
use App\Livewire\GestionCargosMandante;
use App\Livewire\GestionContratistas;
use App\Livewire\GestionReglasDocumentales; // <-- IMPORTACIÓN AÑADIDA

// --- Importaciones de Componentes para Contratista_Admin ---
use App\Livewire\FichaContratista;
use App\Livewire\GestionTrabajadoresContratista;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::view('/', 'welcome');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::view('profile', 'profile')
    ->middleware(['auth'])
    ->name('profile');

// --- RUTAS PARA ASEM_Admin ---
Route::prefix('gestion')->middleware(['auth', 'role:ASEM_Admin'])->name('gestion.')->group(function () {
    Route::get('/listados-universales', GestionListadosUniversalesHub::class)->name('listados.hub');

    // Catálogos / Listados Universales
    Route::get('/documentos', ListarNombreDocumentos::class)->name('documentos');
    Route::get('/rubros', GestionRubros::class)->name('rubros');
    Route::get('/tipos-empresa-legal', GestionTiposEmpresaLegal::class)->name('tipos-empresa-legal');
    Route::get('/nacionalidades', GestionNacionalidades::class)->name('nacionalidades');
    Route::get('/tipos-condicion-personal', GestionTiposCondicionPersonal::class)->name('tipos-condicion-personal');
    Route::get('/sexos', GestionSexos::class)->name('sexos');
    Route::get('/estados-civiles', GestionEstadosCiviles::class)->name('estados-civiles');
    Route::get('/etnias', GestionEtnias::class)->name('etnias');
    Route::get('/niveles-educacionales', GestionNivelesEducacionales::class)->name('niveles-educacionales');
    Route::get('/criterios-evaluacion', GestionCriteriosEvaluacion::class)->name('criterios-evaluacion');
    Route::get('/sub-criterios', GestionSubCriterios::class)->name('sub-criterios');
    Route::get('/condiciones-fecha-ingreso', GestionCondicionesFechaIngreso::class)->name('condiciones-fecha-ingreso');
    Route::get('/configuraciones-validacion', GestionConfiguracionesValidacion::class)->name('configuraciones-validacion');
    Route::get('/textos-rechazo', GestionTextosRechazo::class)->name('textos-rechazo');
    Route::get('/aclaraciones-criterio', GestionAclaracionesCriterio::class)->name('aclaraciones-criterio');
    Route::get('/observaciones-documento', GestionObservacionesDocumento::class)->name('observaciones-documento');
    Route::get('/tipos-carga', GestionTiposCarga::class)->name('tipos-carga');
    Route::get('/tipos-vencimiento', GestionTiposVencimiento::class)->name('tipos-vencimiento');
    Route::get('/tipos-entidad-controlable', GestionTiposEntidadControlable::class)->name('tipos-entidad-controlable');
    Route::get('/tipos-condicion', GestionTiposCondicion::class)->name('tipos-condicion');
    Route::get('/rangos-cantidad-trabajadores', GestionRangosCantidadTrabajadores::class)->name('rangos-cantidad-trabajadores');
    Route::get('/mutualidades', GestionMutualidades::class)->name('mutualidades');
    Route::get('/regiones', GestionRegiones::class)->name('regiones');
    Route::get('/comunas', GestionComunas::class)->name('comunas');
    Route::get('/formatos-muestra', GestionFormatosMuestra::class)->name('formatos-muestra');
    
    // Gestión Principal de Entidades
    Route::get('/mandantes', GestionMandantes::class)->name('mandantes');
    Route::get('/unidades-organizacionales-mandante', GestionUnidadesOrganizacionalesMandante::class)->name('unidades-organizacionales-mandante');
    Route::get('/cargos-mandante', GestionCargosMandante::class)->name('cargos-mandante');
    Route::get('/contratistas', GestionContratistas::class)->name('contratistas');
    Route::get('/reglas-documentales', GestionReglasDocumentales::class)->name('reglas-documentales'); // <-- RUTA AÑADIDA
});

// --- RUTAS PARA Contratista_Admin ---
Route::prefix('contratista')->middleware(['auth', 'role:Contratista_Admin'])->name('contratista.')->group(function () {
    Route::get('/mi-ficha', FichaContratista::class)->name('mi-ficha');
    Route::get('/trabajadores', GestionTrabajadoresContratista::class)->name('trabajadores');
    // Aquí podrías agregar más rutas específicas para Contratista_Admin en el futuro
});

// Ruta consulta documentos (compartida o solo para ASEM_Admin dependiendo de tu lógica)
// Si es compartida, asegúrate que el rol permita a ambos o ajusta el middleware
Route::get('/gestion/documentos/consulta', ListarNombreDocumentos::class) // Ajustar nombre del componente si es necesario
    ->middleware(['auth', 'role:ASEM_Admin|Contratista_Admin']) // Ejemplo si ambos pueden consultar
    ->name('gestion.documentos.consulta');


require __DIR__.'/auth.php';