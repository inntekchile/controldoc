<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\User;
use App\Models\Contratista;
use App\Models\Mandante;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;

class GestionUsuarios extends Component
{
    use WithPagination;

    // Propiedades para el modal de edición/creación
    public $isModalOpen = false;
    public $userId;
    public $name, $email, $password, $password_confirmation;
    public $selectedRole, $contratista_id, $mandante_id;

    // NUEVA PROPIEDAD PARA CONFIRMAR ELIMINACIÓN
    public $confirmingUserDeletionId;
    
    // Propiedades para los filtros
    public $search = ''; 
    public $filtroEmail = '';
    public $filtroRol = '';
    public $filtroEstado = '';

    protected $paginationTheme = 'bootstrap';

    public function render()
    {
        $query = User::query()->with('roles');

        if (!empty($this->search)) $query->where('name', 'like', '%' . $this->search . '%');
        if (!empty($this->filtroEmail)) $query->where('email', 'like', '%' . $this->filtroEmail . '%');
        if (!empty($this->filtroRol)) $query->whereHas('roles', function ($q) { $q->where('name', $this->filtroRol); });
        if ($this->filtroEstado !== '') $query->where('is_active', $this->filtroEstado);

        $users = $query->orderBy('name')->paginate(10);
        
        $roles = Role::orderBy('name')->get();
        $contratistas = Contratista::orderBy('razon_social')->get();
        $mandantes = Mandante::orderBy('razon_social')->get();

        return view('livewire.gestion-usuarios', [
            'users' => $users,
            'roles' => $roles,
            'contratistas' => $contratistas,
            'mandantes' => $mandantes,
        ])->layout('layouts.app');
    }

    public function updated($propertyName)
    {
        if (in_array($propertyName, ['search', 'filtroEmail', 'filtroRol', 'filtroEstado'])) {
            $this->resetPage();
        }
    }

    private function resetInputFields()
    {
        $this->userId = null;
        $this->name = '';
        $this->email = '';
        $this->password = '';
        $this->password_confirmation = '';
        $this->selectedRole = '';
        $this->contratista_id = null;
        $this->mandante_id = null;
        $this->confirmingUserDeletionId = null;
    }

    public function openModal() { $this->isModalOpen = true; }
    public function closeModal() { $this->isModalOpen = false; $this->resetInputFields(); }

    public function create()
    {
        $this->resetInputFields();
        $this->openModal();
    }

    public function edit($id)
    {
        $user = User::findOrFail($id);
        $this->userId = $id;
        $this->name = $user->name;
        $this->email = $user->email;
        $this->selectedRole = $user->roles->first()->name ?? '';
        $this->contratista_id = $user->contratista_id;
        $this->mandante_id = $user->mandante_id;
        $this->password = '';
        $this->password_confirmation = '';
        $this->openModal();
    }

    public function save()
    {
        // ... (La función save se mantiene exactamente igual que en la versión anterior)
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $this->userId,
            'selectedRole' => 'required|exists:roles,name',
        ];

        if (!$this->userId || !empty($this->password)) {
            $rules['password'] = ['required', 'confirmed', Rules\Password::defaults()];
        }

        if ($this->selectedRole === 'Contratista_Admin') {
            $rules['contratista_id'] = 'required|exists:contratistas,id';
        }
        if ($this->selectedRole === 'Mandante_Admin') {
            $rules['mandante_id'] = 'required|exists:mandantes,id';
        }
        
        $this->validate($rules);

        $data = [
            'name' => $this->name,
            'email' => $this->email,
            'user_type' => str_contains(strtolower($this->selectedRole), 'contratista') ? 'contratista' : (str_contains(strtolower($this->selectedRole), 'mandante') ? 'mandante' : 'asem'),
            'contratista_id' => $this->selectedRole === 'Contratista_Admin' ? $this->contratista_id : null,
            'mandante_id' => $this->selectedRole === 'Mandante_Admin' ? $this->mandante_id : null,
        ];

        if (!empty($this->password)) {
            $data['password'] = Hash::make($this->password);
        }

        $user = User::updateOrCreate(['id' => $this->userId], $data);
        $user->syncRoles([$this->selectedRole]);
        session()->flash('message', $this->userId ? 'Usuario actualizado exitosamente.' : 'Usuario creado exitosamente.');
        $this->closeModal();
    }
    
    public function toggleStatus(User $user)
    {
        if ($user->id === auth()->id()) {
            session()->flash('error', 'No puedes desactivar tu propia cuenta.');
            return;
        }
        $user->is_active = !$user->is_active;
        $user->save();
        session()->flash('message', 'Estado del usuario actualizado exitosamente.');
    }

    // ============= FUNCIONES PARA ELIMINAR USUARIO =============
    public function confirmUserDeletion($id)
    {
        // No permitir que el usuario se elimine a sí mismo
        if ($id === auth()->id()) {
            session()->flash('error', 'No puedes eliminar tu propia cuenta.');
            return;
        }
        $this->confirmingUserDeletionId = $id;
    }

    public function deleteUser()
    {
        $user = User::find($this->confirmingUserDeletionId);
        if ($user) {
            $user->delete();
            session()->flash('message', 'Usuario eliminado exitosamente.');
        } else {
            session()->flash('error', 'No se pudo encontrar el usuario para eliminar.');
        }
        $this->confirmingUserDeletionId = null; // Cerrar el modal de confirmación
    }
    // ==========================================================
}