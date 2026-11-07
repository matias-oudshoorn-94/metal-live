<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * Canal WhatsApp vía WhatsApp Business Cloud API (Meta).
 * Se activa definiendo WHATSAPP_TOKEN y WHATSAPP_PHONE_ID en el .env.
 * Nota: fuera de la ventana de 24 h Meta exige plantillas aprobadas; ver README.
 */
final class WhatsAppChannel implements Channel
{
    public function name(): string
    {
        return 'whatsapp';
    }

    public function enabled(): bool
    {
        return (bool) env('WHATSAPP_TOKEN') && (bool) env('WHATSAPP_PHONE_ID');
    }

    public function addressFor(array $user): ?string
    {
        if (!(int) ($user['notify_whatsapp'] ?? 0) || empty($user['phone'])) {
            return null;
        }
        $digits = preg_replace('/\D+/', '', (string) $user['phone']);
        return strlen($digits) >= 10 ? $digits : null;
    }

    public function render(Message $message): string
    {
        return '*' . $message->subject . "*\n\n" . $message->toText();
    }

    public function send(string $to, string $subject, string $body): void
    {
        $url = sprintf('https://graph.facebook.com/%s/%s/messages', env('WHATSAPP_API_VERSION', 'v21.0'), env('WHATSAPP_PHONE_ID'));
        $payload = json_encode([
            'messaging_product' => 'whatsapp',
            'to' => $to,
            'type' => 'text',
            'text' => ['body' => mb_substr($body, 0, 4000), 'preview_url' => true],
        ], JSON_THROW_ON_ERROR);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . env('WHATSAPP_TOKEN'),
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);

        if ($response === false || $status >= 300) {
            throw new \RuntimeException('WhatsApp API: ' . ($error ?: "HTTP {$status} " . mb_substr((string) $response, 0, 300)));
        }
    }
}
