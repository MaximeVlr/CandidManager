<?php

declare(strict_types=1);

namespace App\DTO;

use App\EventSubscriber\Exception\InvalidApplicationsJsonException;

final readonly class MailerSettingsUpdateRequest
{
    public function __construct(
        public string $provider,
        public bool $enabled,
        public string $fromEmail,
        public string $fromName,
        public string $username,
        public ?string $password,
        public string $host,
        public int $port,
        public string $encryption,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $errors = [];
        $provider = isset($payload['provider']) && is_string($payload['provider']) ? $payload['provider'] : '';

        if (!in_array($provider, ['gmail', 'brevo'], true)) {
            $errors[] = ['index' => null, 'field' => 'provider', 'message' => 'Le mailer doit etre gmail ou brevo.'];
        }

        if ($provider === 'brevo') {
            if (!isset($payload['enabled']) || !is_bool($payload['enabled'])) {
                $errors[] = ['index' => null, 'field' => 'enabled', 'message' => 'Le champ doit etre un booleen.'];
            }

            if ($errors !== []) {
                throw new InvalidApplicationsJsonException($errors);
            }

            return new self(
                'brevo',
                $payload['enabled'],
                '',
                'CandidManager',
                '',
                null,
                'smtp.gmail.com',
                587,
                'tls',
            );
        }

        foreach (['from_email', 'from_name', 'username', 'host', 'encryption'] as $field) {
            if (!isset($payload[$field]) || !is_string($payload[$field]) || trim($payload[$field]) === '') {
                $errors[] = ['index' => null, 'field' => $field, 'message' => 'Champ obligatoire.'];
            }
        }

        if (!isset($payload['enabled']) || !is_bool($payload['enabled'])) {
            $errors[] = ['index' => null, 'field' => 'enabled', 'message' => 'Le champ doit etre un booleen.'];
        }

        if (!isset($payload['port']) || !is_int($payload['port'])) {
            $errors[] = ['index' => null, 'field' => 'port', 'message' => 'Le port doit etre un entier.'];
        }

        if (isset($payload['from_email']) && is_string($payload['from_email']) && filter_var($payload['from_email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = ['index' => null, 'field' => 'from_email', 'message' => 'Adresse expediteur invalide.'];
        }

        if (isset($payload['username']) && is_string($payload['username']) && filter_var($payload['username'], FILTER_VALIDATE_EMAIL) === false) {
            $errors[] = ['index' => null, 'field' => 'username', 'message' => 'Login Gmail invalide.'];
        }

        if (isset($payload['encryption']) && is_string($payload['encryption']) && !in_array($payload['encryption'], ['tls', 'ssl'], true)) {
            $errors[] = ['index' => null, 'field' => 'encryption', 'message' => 'Le chiffrement doit etre tls ou ssl.'];
        }

        if ($errors !== []) {
            throw new InvalidApplicationsJsonException($errors);
        }

        return new self(
            'gmail',
            $payload['enabled'],
            trim($payload['from_email']),
            trim($payload['from_name']),
            trim($payload['username']),
            isset($payload['password']) && is_string($payload['password']) && trim($payload['password']) !== '' ? $payload['password'] : null,
            trim($payload['host']),
            $payload['port'],
            $payload['encryption'],
        );
    }
}
