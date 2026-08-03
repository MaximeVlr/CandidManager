<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ImportApplicationItem;
use App\Entity\Enum\ResponseStatus;
use App\EventSubscriber\Exception\InvalidApplicationsJsonException;

final readonly class ApplicationJsonValidator
{
    public function __construct(
        private WebUrlValidator $webUrlValidator,
    ) {}

    /**
     * @return list<ImportApplicationItem>
     */
    public function validate(string $json): array
    {
        try {
            $payload = json_decode($json, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new InvalidApplicationsJsonException([
                ['index' => null, 'field' => null, 'message' => 'JSON invalide: ' . $exception->getMessage()],
            ]);
        }

        if (!is_array($payload)) {
            throw new InvalidApplicationsJsonException([
                ['index' => null, 'field' => null, 'message' => 'La racine du JSON doit etre un objet.'],
            ]);
        }

        if (!array_key_exists('applications', $payload) || !is_array($payload['applications'])) {
            throw new InvalidApplicationsJsonException([
                ['index' => null, 'field' => 'applications', 'message' => 'Le champ applications est obligatoire et doit etre un tableau.'],
            ]);
        }

        $errors = [];
        $items = [];
        $seenRecipients = [];

        foreach ($payload['applications'] as $index => $rawApplication) {
            if (!is_array($rawApplication)) {
                $errors[] = ['index' => $index, 'field' => null, 'message' => 'Chaque candidature doit etre un objet.'];
                continue;
            }

            $this->validateRequiredString($rawApplication, $index, 'company', 180, $errors);
            $this->validateRequiredString($rawApplication, $index, 'location', 120, $errors);
            $this->validateRequiredString($rawApplication, $index, 'email', 180, $errors);
            $this->validateRequiredString($rawApplication, $index, 'subject', 180, $errors);
            $this->validateRequiredString($rawApplication, $index, 'custom_message', 10000, $errors);
            $this->validateRequiredString($rawApplication, $index, 'official_source_url', 2048, $errors);
            $this->validateRequiredBoolean($rawApplication, $index, 'sent', $errors);
            // $this->validateRequiredString($rawApplication, $index, 'response', 40, $errors);
            $this->validateRequiredBoolean($rawApplication, $index, 'follow_up', $errors);

            if (isset($rawApplication['email']) && is_string($rawApplication['email']) && filter_var($rawApplication['email'], FILTER_VALIDATE_EMAIL) === false) {
                $errors[] = ['index' => $index, 'field' => 'email', 'message' => 'Email invalide.'];
            }

            if (isset($rawApplication['official_source_url']) && is_string($rawApplication['official_source_url']) && !$this->webUrlValidator->isValid($rawApplication['official_source_url'])) {
                $errors[] = ['index' => $index, 'field' => 'official_source_url', 'message' => 'URL invalide.'];
            }

            // if (isset($rawApplication['response']) && is_string($rawApplication['response']) && ResponseStatus::tryFrom($rawApplication['response']) === null) {
            //     $errors[] = ['index' => $index, 'field' => 'response', 'message' => 'Statut de reponse invalide.'];
            // }

            if (isset($rawApplication['company'], $rawApplication['email']) && is_string($rawApplication['company']) && is_string($rawApplication['email'])) {
                $key = mb_strtolower(trim($rawApplication['email']) . '|' . trim($rawApplication['company']));
                if (isset($seenRecipients[$key])) {
                    $errors[] = ['index' => $index, 'field' => 'email', 'message' => 'Doublon dans le fichier pour cette entreprise et cet email.'];
                }
                $seenRecipients[$key] = true;
            }

            if ($this->hasErrorsForIndex($errors, $index)) {
                continue;
            }

            $items[] = new ImportApplicationItem(
                trim($rawApplication['company']),
                trim($rawApplication['location']),
                trim($rawApplication['email']),
                trim($rawApplication['subject']),
                trim($rawApplication['custom_message']),
                $this->webUrlValidator->normalize($rawApplication['official_source_url']),
                $rawApplication['sent'],
                $rawApplication['response'],
                $rawApplication['follow_up'],
            );
        }

        if ($errors !== []) {
            throw new InvalidApplicationsJsonException($errors);
        }

        return $items;
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<array{index: int|null, field: string|null, message: string}> $errors
     */
    private function validateRequiredString(array $payload, int $index, string $field, int $maxLength, array &$errors): void
    {
        if (!array_key_exists($field, $payload)) {
            $errors[] = ['index' => $index, 'field' => $field, 'message' => 'Champ obligatoire manquant.'];
            return;
        }

        if (!is_string($payload[$field]) || trim($payload[$field]) === '') {
            $errors[] = ['index' => $index, 'field' => $field, 'message' => 'Le champ doit etre une chaine non vide.'];
            return;
        }

        if (mb_strlen(trim($payload[$field])) > $maxLength) {
            $errors[] = ['index' => $index, 'field' => $field, 'message' => sprintf('Le champ depasse %d caracteres.', $maxLength)];
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @param list<array{index: int|null, field: string|null, message: string}> $errors
     */
    private function validateRequiredBoolean(array $payload, int $index, string $field, array &$errors): void
    {
        if (!array_key_exists($field, $payload)) {
            $errors[] = ['index' => $index, 'field' => $field, 'message' => 'Champ obligatoire manquant.'];
            return;
        }

        if (!is_bool($payload[$field])) {
            $errors[] = ['index' => $index, 'field' => $field, 'message' => 'Le champ doit etre un booleen.'];
        }
    }

    /**
     * @param list<array{index: int|null, field: string|null, message: string}> $errors
     */
    private function hasErrorsForIndex(array $errors, int $index): bool
    {
        foreach ($errors as $error) {
            if ($error['index'] === $index) {
                return true;
            }
        }

        return false;
    }
}
