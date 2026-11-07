<?php

declare(strict_types=1);

namespace App\Services\Notifications;

final class EmailChannel implements Channel
{
    public function name(): string
    {
        return 'email';
    }

    public function enabled(): bool
    {
        return true;
    }

    public function addressFor(array $user): ?string
    {
        return (int) ($user['notify_email'] ?? 1) && !empty($user['email']) ? $user['email'] : null;
    }

    public function render(Message $message): string
    {
        return $message->toHtml();
    }

    public function send(string $to, string $subject, string $body): void
    {
        (new Mailer())->send($to, $subject, $body);
    }
}
