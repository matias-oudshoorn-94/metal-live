<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Auth;
use App\Core\Controller;
use App\Core\Session;

final class AuthController extends Controller
{
    public function loginForm(): void
    {
        $this->render('admin/login', ['title' => 'Ingresar'], 'auth');
    }

    public function login(): void
    {
        $email = (string) $this->request->input('email', '');
        $password = (string) ($_POST['password'] ?? '');

        if ($email === '' || $password === '') {
            Session::flash('_old_input', ['email' => $email]);
            $this->back('Ingresá correo y contraseña.');
        }

        $error = Auth::attempt($email, $password, $this->request->ip());
        if ($error !== null) {
            Session::flash('_old_input', ['email' => $email]);
            $this->back($error);
        }

        $target = Auth::can('dashboard.view') ? '/admin' : (Auth::can('deliveries.view_own') ? '/admin/entregas' : '/admin/perfil');
        $this->redirect($target);
    }

    public function logout(): void
    {
        Auth::logout();
        $this->redirect('/admin/login');
    }
}
