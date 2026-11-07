<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\HttpException;
use App\Core\Session;
use App\Services\Audit;

final class UserController extends Controller
{
    public function index(): void
    {
        $role = (int) $this->request->query('role', 0);
        $rows = Database::fetchAll(
            'SELECT u.*, r.name AS role_name FROM users u JOIN roles r ON r.id = u.role_id'
            . ($role ? ' WHERE u.role_id = ?' : '') . ' ORDER BY u.active DESC, u.name',
            $role ? [$role] : []
        );
        $this->render('admin/users/index', ['title' => 'Usuarios', 'rows' => $rows, 'roles' => $this->roles(), 'role' => $role]);
    }

    public function create(): void
    {
        $this->render('admin/users/form', ['title' => 'Nuevo usuario', 'user' => null, 'roles' => $this->roles()]);
    }

    public function store(): void
    {
        $data = $this->input(null);
        $id = Database::insert('users', [...$data, 'created_at' => now(), 'updated_at' => now()]);
        Audit::log('create', 'users', $id, $data['email']);
        $this->redirect('/admin/usuarios', 'Usuario creado.');
    }

    public function edit(string $id): void
    {
        $this->render('admin/users/form', ['title' => 'Editar usuario', 'user' => $this->find($id), 'roles' => $this->roles()]);
    }

    public function update(string $id): void
    {
        $user = $this->find($id);
        $data = $this->input($user);
        if ((int) $user['id'] === Auth::id() && (!$data['active'] || (int) $data['role_id'] !== (int) $user['role_id'])) {
            $this->back('No podés desactivarte ni cambiar tu propio rol.');
        }
        Database::update('users', [...$data, 'updated_at' => now()], (int) $user['id']);
        Audit::log('update', 'users', (int) $user['id'], isset($data['password_hash']) ? 'con cambio de contraseña' : null);
        $this->redirect('/admin/usuarios', 'Usuario actualizado.');
    }

    public function destroy(string $id): void
    {
        $user = $this->find($id);
        if ((int) $user['id'] === Auth::id()) {
            $this->back('No podés eliminar tu propio usuario.');
        }
        $hasHistory = Database::value('SELECT 1 FROM surgeries WHERE seller_id = ? OR driver_id = ? OR created_by = ? LIMIT 1', [$user['id'], $user['id'], $user['id']]);
        if ($hasHistory) {
            Database::update('users', ['active' => 0, 'updated_at' => now()], (int) $user['id']);
            Audit::log('disable', 'users', (int) $user['id']);
            $this->redirect('/admin/usuarios', 'El usuario tiene historial: se desactivó.');
        }
        Database::delete('users', (int) $user['id']);
        Audit::log('delete', 'users', (int) $user['id'], $user['email']);
        $this->redirect('/admin/usuarios', 'Usuario eliminado.');
    }

    private function find(string $id): array
    {
        return Database::fetch('SELECT * FROM users WHERE id = ?', [(int) $id]) ?? throw new HttpException(404);
    }

    private function roles(): array
    {
        return Database::fetchAll('SELECT id, name FROM roles ORDER BY name');
    }

    private function input(?array $user): array
    {
        $data = $this->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'max:40',
            'role_id' => 'required|exists:roles',
            'commission_pct' => 'numeric|min_value:0',
            'notify_email' => 'bool',
            'notify_whatsapp' => 'bool',
            'active' => 'bool',
            'password' => ($user ? '' : 'required|') . 'password',
        ], ['name' => 'Nombre', 'email' => 'Correo', 'phone' => 'Teléfono', 'role_id' => 'Rol', 'commission_pct' => 'Comisión', 'password' => 'Contraseña']);

        $data['email'] = mb_strtolower($data['email']);
        if (Database::value('SELECT 1 FROM users WHERE email = ? AND id <> ?', [$data['email'], $user['id'] ?? 0])) {
            Session::flash('_errors', ['email' => 'Ya existe un usuario con ese correo.']);
            Session::flash('_old_input', array_diff_key($_POST, ['password' => 1]));
            $this->back('Revisá los campos marcados.');
        }
        if (($_POST['password'] ?? '') !== ($_POST['password_confirmation'] ?? '')) {
            Session::flash('_errors', ['password' => 'Las contraseñas no coinciden.']);
            Session::flash('_old_input', array_diff_key($_POST, ['password' => 1, 'password_confirmation' => 1]));
            $this->back('Revisá los campos marcados.');
        }
        if ($data['password']) {
            $data['password_hash'] = Auth::hash($data['password']);
        }
        unset($data['password']);
        $data['commission_pct'] ??= '0';
        return $data;
    }
}
