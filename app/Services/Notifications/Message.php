<?php

declare(strict_types=1);

namespace App\Services\Notifications;

/** Contenido de un aviso, independiente del canal por el que se envíe. */
final readonly class Message
{
    /**
     * @param array<string, string> $details  pares etiqueta => valor
     */
    public function __construct(
        public string $subject,
        public string $intro,
        public array $details = [],
        public ?string $actionUrl = null,
        public ?string $actionLabel = null,
        public ?string $outro = null,
    ) {
    }

    public function withAction(string $url, string $label): self
    {
        return new self($this->subject, $this->intro, $this->details, $url, $label, $this->outro);
    }

    public function toText(): string
    {
        $lines = [$this->intro, ''];
        foreach ($this->details as $label => $value) {
            $lines[] = "• {$label}: {$value}";
        }
        if ($this->actionUrl) {
            $lines[] = '';
            $lines[] = "{$this->actionLabel}: {$this->actionUrl}";
        }
        if ($this->outro) {
            $lines[] = '';
            $lines[] = $this->outro;
        }
        return implode("\n", $lines);
    }

    public function toHtml(): string
    {
        return \App\Core\View::partial('emails/notification', ['message' => $this]);
    }
}
