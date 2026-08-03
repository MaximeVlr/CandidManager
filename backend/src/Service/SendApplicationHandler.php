<?php

declare(strict_types=1);

namespace App\Service;

use App\Command\Application\SendApplicationCommand;
use App\DTO\SendApplicationResult;
use App\Entity\ApplicationSendLog;
use App\Entity\Enum\SendLogStatus;
use App\Repository\JobApplicationRepositoryInterface;
use App\Repository\MailTemplateRepositoryInterface;
use App\Service\ApplicationMailerInterface;

final readonly class SendApplicationHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
        private MailTemplateRepositoryInterface $mailTemplates,
        private ApplicationMailerInterface $mailer,
    ) {
    }

    public function __invoke(SendApplicationCommand $command): SendApplicationResult
    {
        $jobApplication = $this->jobApplications->findById($command->id);
        $template = $command->countFollowUp
            ? ($this->mailTemplates->findByName('follow_up') ?? $this->mailTemplates->findDefault())
            : $this->mailTemplates->findDefault();

        if ($jobApplication === null) {
            return new SendApplicationResult(false, 'Candidature introuvable.', null);
        }

        if ($template === null) {
            return new SendApplicationResult(false, 'Template par defaut introuvable.', $jobApplication);
        }

        $startedAt = microtime(true);
        $jobApplication->markAsSending();
        $this->jobApplications->flush();

        try {
            $this->mailer->send($jobApplication, $template);
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $jobApplication->markAsSent();
            if ($command->countFollowUp) {
                $jobApplication->incrementFollowUpCount();
            }
            $jobApplication->addSendLog(new ApplicationSendLog(
                $jobApplication,
                SendLogStatus::Success,
                durationMs: $durationMs,
            ));
            $this->jobApplications->flush();

            return new SendApplicationResult(true, 'Email envoye.', $jobApplication);
        } catch (\Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);
            $message = $this->sanitizeErrorMessage($exception->getMessage());

            $jobApplication->markAsFailed($message);
            $jobApplication->addSendLog(new ApplicationSendLog(
                $jobApplication,
                SendLogStatus::Failure,
                smtpCode: $this->extractSmtpCode($exception->getMessage()),
                errorMessage: $message,
                durationMs: $durationMs,
            ));
            $this->jobApplications->flush();

            return new SendApplicationResult(false, $message, $jobApplication);
        }
    }

    private function sanitizeErrorMessage(string $message): string
    {
        return preg_replace('/smtp:\/\/[^@\s]+@/i', 'smtp://***@', $message) ?? $message;
    }

    private function extractSmtpCode(string $message): ?string
    {
        if (preg_match('/\b([245][0-9]{2})\b/', $message, $matches) === 1) {
            return $matches[1];
        }

        return null;
    }
}
