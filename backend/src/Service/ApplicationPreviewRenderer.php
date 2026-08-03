<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ApplicationPreviewResponse;
use App\Entity\JobApplication;
use App\Entity\MailTemplate;

final readonly class ApplicationPreviewRenderer
{
    public function render(JobApplication $jobApplication, MailTemplate $mailTemplate): ApplicationPreviewResponse
    {
        $variables = $jobApplication->templateVariables();

        return new ApplicationPreviewResponse(
            $variables['subject'],
            $this->replaceVariables($mailTemplate->htmlBody(), $variables, escapeHtml: true),
            $this->replaceVariables($mailTemplate->textBody(), $variables, escapeHtml: false),
        );
    }

    /**
     * @param array<string, string> $variables
     */
    private function replaceVariables(string $template, array $variables, bool $escapeHtml): string
    {
        return preg_replace_callback(
            '/{{\s*([a-zA-Z0-9_]+)\s*}}/',
            static function (array $matches) use ($variables, $escapeHtml): string {
                $variableName = $matches[1];
                $value = $variables[$variableName] ?? '';

                if ($escapeHtml) {
                    $escapedValue = htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');

                    if ($variableName === 'custom_message') {
                        return nl2br($escapedValue, false);
                    }

                    return $escapedValue;
                }

                return $value;
            },
            $template,
        ) ?? $template;
    }
}
