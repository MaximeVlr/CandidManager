<?php

declare(strict_types=1);

namespace App\DTO;

use App\EventSubscriber\Exception\InvalidApplicationsJsonException;

final readonly class TemplateUpdateRequest
{
    public function __construct(
        public string $htmlBody,
        public string $textBody,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $errors = [];

        if (!array_key_exists('html_body', $payload) || !is_string($payload['html_body'])) {
            $errors[] = ['index' => null, 'field' => 'html_body', 'message' => 'Le template HTML est obligatoire.'];
        }

        if (!array_key_exists('text_body', $payload) || !is_string($payload['text_body'])) {
            $errors[] = ['index' => null, 'field' => 'text_body', 'message' => 'Le template texte est obligatoire.'];
        }

        if ($errors !== []) {
            throw new InvalidApplicationsJsonException($errors);
        }

        return new self($payload['html_body'], $payload['text_body']);
    }
}
