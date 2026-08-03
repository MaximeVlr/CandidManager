<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\Enum\ResponseStatus;
use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use App\Service\WebUrlValidator;

final readonly class ApplicationUpdateRequest
{
    public function __construct(
        public string $company,
        public string $location,
        public string $email,
        public string $subject,
        public string $customMessage,
        public string $officialSourceUrl,
        public ResponseStatus $responseStatus,
        public bool $followUp,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload, WebUrlValidator $webUrlValidator): self
    {
        $errors = [];

        self::validateString($payload, 'company', 180, $errors);
        self::validateString($payload, 'location', 120, $errors);
        self::validateString($payload, 'email', 180, $errors);
        self::validateString($payload, 'subject', 180, $errors);
        self::validateString($payload, 'custom_message', 10000, $errors);
        self::validateString($payload, 'official_source_url', 2048, $errors);

        if (isset($payload['email']) && is_string($payload['email']) && filter_var($payload['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = ['index' => null, 'field' => 'email', 'message' => 'Email invalide.'];
        }

        if (isset($payload['official_source_url']) && is_string($payload['official_source_url']) && !$webUrlValidator->isValid($payload['official_source_url'])) {
            $errors[] = ['index' => null, 'field' => 'official_source_url', 'message' => 'URL invalide.'];
        }

        if (!array_key_exists('response', $payload) || !is_string($payload['response'])) {
            $errors[] = ['index' => null, 'field' => 'response', 'message' => 'Statut de reponse obligatoire.'];
        }

        $responseStatus = isset($payload['response']) && is_string($payload['response'])
            ? ResponseStatus::tryFrom($payload['response'])
            : null;

        if ($responseStatus === null) {
            $errors[] = ['index' => null, 'field' => 'response', 'message' => 'Statut de reponse invalide.'];
        }

        if (!array_key_exists('follow_up', $payload) || !is_bool($payload['follow_up'])) {
            $errors[] = ['index' => null, 'field' => 'follow_up', 'message' => 'Le champ doit etre un booleen.'];
        }

        if ($errors !== []) {
            throw new InvalidApplicationsJsonException($errors);
        }

        return new self(
            trim($payload['company']),
            trim($payload['location']),
            trim($payload['email']),
            trim($payload['subject']),
            trim($payload['custom_message']),
            $webUrlValidator->normalize($payload['official_source_url']),
            $responseStatus,
            $payload['follow_up'],
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<array{index: int|null, field: string|null, message: string}> $errors
     */
    private static function validateString(array $payload, string $field, int $maxLength, array &$errors): void
    {
        if (!array_key_exists($field, $payload)) {
            $errors[] = ['index' => null, 'field' => $field, 'message' => 'Champ obligatoire manquant.'];
            return;
        }

        if (!is_string($payload[$field]) || trim($payload[$field]) === '') {
            $errors[] = ['index' => null, 'field' => $field, 'message' => 'Le champ doit etre une chaine non vide.'];
            return;
        }

        if (mb_strlen(trim($payload[$field])) > $maxLength) {
            $errors[] = ['index' => null, 'field' => $field, 'message' => sprintf('Le champ depasse %d caracteres.', $maxLength)];
        }
    }
}
