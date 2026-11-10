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
        $this->render('site/home', ['title' => 'Innovación en Implantes, Compromiso en Salud', 'page' => 'home'], 'site');
    }

    public function products(): void
    {
        $this->render('site/products', ['title' => 'Productos', 'page' => 'productos'], 'site');
    }

    public function about(): void
    {
        $this->render('site/about', ['title' => 'Nosotros', 'page' => 'nosotros'], 'site');
    }

    public function faq(): void
    {
        $this->render('site/faq', ['title' => 'Preguntas frecuentes', 'page' => 'preguntas-frecuentes'], 'site');
    }

    public function careers(): void
    {
        $this->render('site/careers', ['title' => 'Sumate al equipo', 'page' => 'sumate-al-equipo'], 'site');
    }

    /** Postulaciones de "Sumate al equipo": quedan en Mensajes web del panel. */
    public function sendApplication(): void
    {
        $this->guardSpam('/sumate-al-equipo');
        $areas = site_job_areas();
        $data = $this->validate([
            'first_name' => 'required|max:80',
            'last_name' => 'required|max:80',
            'email' => 'required|email|max:190',
            'phone' => 'max:40',
            'area' => 'required|in:' . implode(',', array_keys($areas)),
            'message' => 'required|min:10|max:3000',
            'cv_url' => 'max:500|regex:/^https?:\/\/\S+$/i',
        ], ['first_name' => 'Nombre', 'last_name' => 'Apellido', 'email' => 'Correo', 'phone' => 'Teléfono', 'area' => 'Aspirante a', 'message' => 'Presentación', 'cv_url' => 'Curriculum Vitae']);

        $name = $data['first_name'] . ' ' . $data['last_name'];
        $message = "Postulación «Sumate al equipo» · Aspirante a: {$areas[$data['area']]}\n"
            . 'CV: ' . ($data['cv_url'] ?? '—') . "\n\n" . $data['message'];
        $this->storeMessage([
            'name' => $name, 'email' => $data['email'], 'phone' => $data['phone'], 'company' => 'Postulación: ' . $areas[$data['area']], 'message' => $message,
        ], 'Nueva postulación web: ' . $name);
        $this->redirect('/sumate-al-equipo', '¡Gracias por postularte! Revisaremos tu solicitud.');
    }

    public function contact(): void
    {
        $this->render('site/contact', ['title' => 'Contacto', 'page' => 'contacto'], 'site');
    }

    public function sendContact(): void
    {
        $this->guardSpam('/contacto');
        $data = $this->validate([
            'name' => 'required|max:120',
            'email' => 'required|email|max:190',
            'phone' => 'max:40',
            'company' => 'max:160',
            'message' => 'required|min:10|max:3000',
            'privacy' => 'required',
        ], ['name' => 'Nombre', 'email' => 'Correo', 'phone' => 'Teléfono', 'company' => 'Institución', 'message' => 'Mensaje', 'privacy' => 'Aceptar la política de privacidad']);
        unset($data['privacy']);

        $this->storeMessage($data, 'Nuevo contacto web: ' . $data['name']);
        $this->redirect('/contacto', '¡Gracias! Te responderemos a la brevedad.');
    }

    /** Honeypot anti-spam y límite simple por IP (5 mensajes por hora). */
    private function guardSpam(string $back): void
    {
        if (!empty($_POST['website'])) {
            $this->redirect($back, '¡Gracias! Te responderemos a la brevedad.');
        }
        $recent = (int) Database::value('SELECT COUNT(*) FROM contact_messages WHERE ip = ? AND created_at > ?', [$this->request->ip(), date('Y-m-d H:i:s', time() - 3600)]);
        if ($recent >= 5) {
            $this->back('Recibimos varios mensajes desde tu conexión. Probá de nuevo más tarde.');
        }
    }

    private function storeMessage(array $data, string $subject): void
    {
        Database::insert('contact_messages', [...$data, 'ip' => $this->request->ip(), 'created_at' => now()]);
        Notifier::send('contact.received', Notifier::usersWithPermission('messages.view'), new Message(
            subject: $subject,
            intro: 'Llegó un mensaje desde el sitio.',
            details: ['Nombre' => $data['name'], 'Correo' => $data['email'], 'Teléfono' => $data['phone'] ?? '—', 'Institución' => $data['company'] ?? '—', 'Mensaje' => $data['message']],
            actionUrl: absolute_url('admin/mensajes'),
            actionLabel: 'Ver mensajes',
        ));
        Session::flash('_old_input', []);
    }
}
