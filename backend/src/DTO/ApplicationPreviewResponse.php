<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ApplicationPreviewResponse
{
    public function __construct(
        public string $subject,
        public string $htmlBody,
        public string $textBody,
    ) {
    }

    /**
     * @return array{subject: string, html_body: string, text_body: string}
     */
    public function toArray(): array
    {
        return [
            'subject' => $this->subject,
            'html_body' => $this->htmlBody,
            'text_body' => $this->textBody,
        ];
    }
}
