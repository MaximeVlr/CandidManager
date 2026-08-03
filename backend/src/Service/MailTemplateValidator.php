<?php

declare(strict_types=1);

namespace App\Service;

use App\EventSubscriber\Exception\InvalidApplicationsJsonException;

final readonly class MailTemplateValidator
{
    private const ALLOWED_VARIABLES = [
        'company',
        'location',
        'email',
        'subject',
        'custom_message',
        'official_source_url',
    ];

    /**
     * @return list<string>
     */
    public function allowedVariables(): array
    {
        return self::ALLOWED_VARIABLES;
    }

    public function validate(string $htmlBody, string $textBody): void
    {
        $errors = [];

        if (trim($htmlBody) === '') {
            $errors[] = ['index' => null, 'field' => 'html_body', 'message' => 'Le template HTML est obligatoire.'];
        }

        if (trim($textBody) === '') {
            $errors[] = ['index' => null, 'field' => 'text_body', 'message' => 'Le template texte est obligatoire.'];
        }

        foreach (['html_body' => $htmlBody, 'text_body' => $textBody] as $field => $body) {
            foreach ($this->extractVariables($body) as $variable) {
                if (!in_array($variable, self::ALLOWED_VARIABLES, true)) {
                    $errors[] = [
                        'index' => null,
                        'field' => $field,
                        'message' => sprintf('Variable inconnue: %s.', $variable),
                    ];
                }
            }
        }

        if ($errors !== []) {
            throw new InvalidApplicationsJsonException($errors);
        }
    }

    /**
     * @return list<string>
     */
    private function extractVariables(string $body): array
    {
        preg_match_all('/{{\s*([a-zA-Z0-9_]+)\s*}}/', $body, $matches);

        return array_values(array_unique($matches[1] ?? []));
    }
}
