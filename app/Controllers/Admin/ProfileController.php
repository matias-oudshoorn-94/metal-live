<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\Audit;

final class ProfileController extends Controller
{
    public function edit(): void
    {
        $this->render('admin/profile', ['title' => 'Mi perfil', 'user' => Auth::user()]);
    }

    public function update(): void
    {
        $data = $this->validate([
            'name' => 'required|max:120',
            'phone' => 'max:40',
            'notify_email' => 'bool',
            'notify_whatsapp' => 'bool',
            'password' => 'password',
        ], ['name' => 'Nombre', 'phone' => 'Teléfono', 'password' => 'Nueva contraseña']);

        $id = (int) Auth::id();
        if ($data['password']) {
            $current = (string) Database::value('SELECT password_hash FROM users WHERE id = ?', [$id]);
            if (!password_verify((string) ($_POST['current_password'] ?? ''), $current)) {
                Session::flash('_errors', ['current_password' => 'La contraseña actual no es correcta.']);
                $this->back('Revisá los campos marcados.');
            }
            if ($_POST['password'] !== ($_POST['password_confirmation'] ?? '')) {
                Session::flash('_errors', ['password' => 'Las contraseñas no coinciden.']);
                $this->back('Revisá los campos marcados.');
            }
            $data['password_hash'] = Auth::hash($data['password']);
        }
        unset($data['password']);
        Database::update('users', [...$data, 'updated_at' => now()], $id);
        if (isset($data['password_hash'])) {
            Auth::refreshPasswordVersion($data['password_hash']);
            Audit::log('password_change', 'users', $id);
        }
        $this->back(success: 'Perfil actualizado.');
    }
}
