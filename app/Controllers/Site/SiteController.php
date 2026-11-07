<?php

declare(strict_types=1);

namespace App\Controllers\Site;

use App\Core\Controller;
use App\Core\Database;
use App\Core\Session;
use App\Services\Notifications\Message;
use App\Services\Notifications\Notifier;

final class SiteController extends Controller
{
    public function home(): void
    {
        $this->render('site/home', ['title' => 'Insumos y prótesis para traumatología', 'page' => 'home'], 'site');
    }

    public function products(): void
    {
        $this->render('site/products', ['title' => 'Productos', 'page' => 'productos'], 'site');
    }

    public function about(): void
    {
        $this->render('site/about', ['title' => 'Nosotros', 'page' => 'nosotros'], 'site');
    }

    public function contact(): void
    {
        $this->render('site/contact', ['title' => 'Contacto', 'page' => 'contacto'], 'site');
    }

    public function sendContact(): void
    {
        // Honeypot anti-spam: los bots completan el campo oculto.
        if (!empty($_POST['website'])) {
            $this->redirect('/contacto', '¡Gracias! Te responderemos a la brevedad.');
        }
        // Límite simple por IP: 5 mensajes por hora.
        $recent = (int) Database::value('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > ?', [$this->request->ip(), date('Y-m-d H:i:s', time() - 3600)]);
        if ($recent >= 5) {
            $this->back('Recibimos varios mensajes desde tu conexión. Probá de nuevo más tarde.');
        }

        $data = $this->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'max:40',
            'company' => 'max:160',
            'message' => 'required|min:10|max:3000',
        ], ['name' => 'Nombre', 'email' => 'Correo', 'phone' => 'Teléfono', 'company' => 'Institución', 'message' => 'Mensaje']);

        Database::insert('contact_messages', [...$data, 'ip' => $this->request->ip(), 'created_at' => now()]);
        Notifier::send('contact.received', Notifier::usersWithPermission('messages.view'), new Message(
            subject: 'Nuevo contacto web: ' . $data['name'],
            intro: 'Llegó un mensaje desde el formulario del sitio.',
            details: ['Nombre' => $data['name'], 'Correo' => $data['email'], 'Teléfono' => $data['phone'] ?? '—', 'Institución' => $data['company'] ?? '—', 'Mensaje' => $data['message']],
            actionUrl: absolute_url('admin/mensajes'),
            actionLabel: 'Ver mensajes',
        ));
        Session::flash('_old_input', []);
        $this->redirect('/contacto', '¡Gracias! Te responderemos a la brevedad.');
    }
}
