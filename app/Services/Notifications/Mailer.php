<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/**
 * Envío de correo sin dependencias externas.
 * MAIL_DRIVER: log (guarda .eml en storage/mail, para desarrollo) | smtp | mail (función mail() de PHP).
 */
final class Mailer
{
    public function send(string $to, string $subject, string $html): void
    {
        $to = $this->clean($to);
        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException("Correo inválido: {$to}");
        }
        $subject = $this->clean($subject);
        $raw = $this->build($to, $subject, $html);

        match ((string) env('MAIL_DRIVER', 'log')) {
            'smtp' => $this->smtp($to, $raw),
            'mail' => $this->nativeMail($to, $subject, $raw),
            default => $this->log($to, $raw),
        };
    }

    private function fromAddress(): string
    {
        return $this->clean((string) env('MAIL_FROM_ADDRESS', 'avisos@metallive.com.ar'));
    }

    private function build(string $to, string $subject, string $html): string
    {
        $boundary = 'b_' . bin2hex(random_bytes(12));
        $fromName = mb_encode_mimeheader((string) env('MAIL_FROM_NAME', 'Metal Live'), 'UTF-8');
        // Versión de texto plano: conserva las URLs de los enlaces y quita el ruido de la maqueta HTML.
        $plain = preg_replace('#<(style|title)[^>]*>.*?</\1>#is', '', $html);
        $plain = preg_replace('#<a\s[^>]*href="([^"]+)"[^>]*>(.*?)</a>#is', '$2: $1', $plain);
        $plain = preg_replace('#</td>\s*<td[^>]*>#i', ': ', $plain);
        $plain = preg_replace('#<br\s*/?>|</p>|</tr>|</h\d>#i', "\n", $plain);
        $plain = html_entity_decode(strip_tags($plain), ENT_QUOTES, 'UTF-8');
        $lines = array_filter(array_map('trim', explode("\n", $plain)), static fn (string $l): bool => $l !== '');
        $text = implode("\n", $lines);
        $domain = substr(strrchr($this->fromAddress(), '@') ?: '@localhost', 1);

        $headers = [
            'Date: ' . date('r'),
            "From: {$fromName} <{$this->fromAddress()}>",
            "To: <{$to}>",
            'Subject: ' . mb_encode_mimeheader($subject, 'UTF-8'),
            'Message-ID: <' . bin2hex(random_bytes(16)) . "@{$domain}>",
            'MIME-Version: 1.0',
            "Content-Type: multipart/alternative; boundary=\"{$boundary}\"",
        ];

        return implode("\r\n", $headers) . "\r\n\r\n"
            . "--{$boundary}\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($text)) . "\r\n"
            . "--{$boundary}\r\nContent-Type: text/html; charset=UTF-8\r\nContent-Transfer-Encoding: base64\r\n\r\n"
            . chunk_split(base64_encode($html)) . "\r\n"
            . "--{$boundary}--\r\n";
    }

    private function log(string $to, string $raw): void
    {
        $dir = BASE_PATH . '/storage/mail';
        if (!is_dir($dir)) {
            mkdir($dir, 0750, true);
        }
        $name = date('Ymd-His') . '-' . preg_replace('/[^a-z0-9]+/i', '_', $to) . '-' . bin2hex(random_bytes(3)) . '.eml';
        file_put_contents($dir . '/' . $name, $raw);
    }

    private function nativeMail(string $to, string $subject, string $raw): void
    {
        [$headers, $body] = explode("\r\n\r\n", $raw, 2);
        $headers = preg_replace('/^(To|Subject): .*\r\n/m', '', $headers . "\r\n");
        if (!mail($to, mb_encode_mimeheader($subject, 'UTF-8'), $body, trim($headers))) {
            throw new \RuntimeException('mail() devolvió false.');
        }
    }

    private function smtp(string $to, string $raw): void
    {
        $host = (string) env('MAIL_HOST', 'localhost');
        $port = (int) env('MAIL_PORT', 587);
        $encryption = (string) env('MAIL_ENCRYPTION', 'tls'); // tls = STARTTLS, ssl = TLS implícito
        $context = stream_context_create(['ssl' => ['verify_peer' => true, 'verify_peer_name' => true]]);
        $socket = @stream_socket_client(
            ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . "{$host}:{$port}",
            $errno,
            $errstr,
            15,
            STREAM_CLIENT_CONNECT,
            $context
        );
        if (!$socket) {
            throw new \RuntimeException("SMTP: no se pudo conectar ({$errstr}).");
        }
        stream_set_timeout($socket, 20);
        $ehlo = 'EHLO ' . (gethostname() ?: 'localhost');

        try {
            $this->expect($socket, [220]);
            $this->command($socket, $ehlo, [250]);
            if ($encryption === 'tls') {
                $this->command($socket, 'STARTTLS', [220]);
                if (!stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT | STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT)) {
                    throw new \RuntimeException('SMTP: falló STARTTLS.');
                }
                $this->command($socket, $ehlo, [250]);
            }
            if (env('MAIL_USERNAME')) {
                $this->command($socket, 'AUTH LOGIN', [334]);
                $this->command($socket, base64_encode((string) env('MAIL_USERNAME')), [334]);
                $this->command($socket, base64_encode((string) env('MAIL_PASSWORD', '')), [235]);
            }
            $this->command($socket, "MAIL FROM:<{$this->fromAddress()}>", [250]);
            $this->command($socket, "RCPT TO:<{$to}>", [250, 251]);
            $this->command($socket, 'DATA', [354]);
            // Dot-stuffing (RFC 5321 §4.5.2)
            $data = preg_replace('/^\./m', '..', $raw);
            $this->command($socket, $data . "\r\n.", [250]);
            $this->command($socket, 'QUIT', [221]);
        } finally {
            fclose($socket);
        }
    }

    private function command($socket, string $command, array $expected): void
    {
        fwrite($socket, $command . "\r\n");
        $this->expect($socket, $expected);
    }

    private function expect($socket, array $expected): void
    {
        $response = '';
        while (($line = fgets($socket, 515)) !== false) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        $code = (int) substr($response, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new \RuntimeException('SMTP: respuesta inesperada: ' . trim($response));
        }
    }

    private function clean(string $value): string
    {
        return trim(str_replace(["\r", "\n", "\0"], '', $value));
    }
}
