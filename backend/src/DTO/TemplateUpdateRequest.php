<?php

declare(strict_types=1);

namespace App\DTO;

use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use Symfony\Component\Uid\Uuid;

final readonly class TemplateUpdateRequest
{
    public function __construct(
        public string $htmlBody,
        public string $textBody,
        public ?string $categoryId = null,
        public bool $categoryProvided = false,
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

        if (array_key_exists('category_id', $payload)
            && $payload['category_id'] !== null
            && (!is_string($payload['category_id']) || !Uuid::isValid($payload['category_id']))) {
            $errors[] = ['index' => null, 'field' => 'category_id', 'message' => 'La catégorie doit être un UUID valide ou null.'];
        }

        if ($errors !== []) {
            throw new InvalidApplicationsJsonException($errors);
        }

        return new self(
            $payload['html_body'],
            $payload['text_body'],
            $payload['category_id'] ?? null,
            array_key_exists('category_id', $payload),
        );
    }
}
