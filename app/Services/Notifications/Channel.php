<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * Un canal de avisos (correo, WhatsApp, ...). Para sumar un canal nuevo alcanza con
 * implementar esta interfaz y registrarlo en Notifier::channels().
 */
interface Channel
{
    public function name(): string;

    public function enabled(): bool;

    /** Dirección del destinatario en este canal, o null si el usuario no lo usa. */
    public function addressFor(array $user): ?string;

    /** Cuerpo que se guarda en la cola para este canal. */
    public function render(Message $message): string;

    /** @throws \Throwable si el envío falla */
    public function send(string $to, string $subject, string $body): void;
}
