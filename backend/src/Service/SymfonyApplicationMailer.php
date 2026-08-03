<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\JobApplication;
use App\Entity\MailerSettings;
use App\Entity\MailTemplate;
use App\EventSubscriber\Exception\MailSendingDisabledException;
use App\Repository\MailerSettingsRepositoryInterface;
use App\Service\ApplicationMailerInterface;
use App\Service\ApplicationPreviewRenderer;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mailer\Transport;
use Symfony\Component\Mime\Address;
use Symfony\Component\Mime\Email;

final readonly class SymfonyApplicationMailer implements ApplicationMailerInterface
{
    public function __construct(
        private MailerInterface $mailer,
        private MailerConfiguration $configuration,
        private ApplicationPreviewRenderer $previewRenderer,
        private CvAttachmentStorage $cvStorage,
        private MailerSettingsRepositoryInterface $mailerSettings,
        private SecretCipher $secretCipher,
    ) {}

    public function send(JobApplication $jobApplication, MailTemplate $mailTemplate): void
    {
        if (!$this->configuration->canSendRealEmails()) {
            throw new MailSendingDisabledException();
        }

        $settings = $this->mailerSettings->get();
        $useGmail = $settings->enabled() && $settings->provider() === 'gmail';
        $fromEmail = $useGmail ? $settings->fromEmail() : $this->configuration->from;
        $fromName = $useGmail ? $settings->fromName() : $this->configuration->fromName;

        $variables = $jobApplication->templateVariables();
        $preview = $this->previewRenderer->render($jobApplication, $mailTemplate);

        if (filter_var($fromEmail, FILTER_VALIDATE_EMAIL) === false) {
            throw new \RuntimeException('Adresse expediteur mailer invalide.');
        }

        $email = (new Email())
            ->from(new Address($fromEmail, $fromName))
            ->to(new Address($variables['email'], $variables['company']))
            ->subject($preview->subject)
            ->html($preview->htmlBody)
            ->text($preview->textBody);

        if ($mailTemplate->cvStoredName() !== null && $mailTemplate->cvOriginalName() !== null) {
            $email->attachFromPath(
                $this->cvStorage->absolutePath($mailTemplate->cvStoredName()),
                $mailTemplate->cvOriginalName(),
                $mailTemplate->cvMimeType() ?? 'application/octet-stream',
            );
        }

        if ($useGmail) {
            if ($settings->encryptedPassword() === null) {
                throw new \RuntimeException('Mot de passe applicatif Gmail manquant.');
            }

            $transport = Transport::fromDsn($this->gmailDsn($settings));
            $transport->send($email);

            return;
        }

        $this->mailer->send($email);
    }

    private function gmailDsn(MailerSettings $settings): string
    {
        $scheme = $settings->encryption() === 'ssl' ? 'smtps' : 'smtp';
        $username = rawurlencode($settings->username());
        $password = rawurlencode($this->secretCipher->decrypt($settings->encryptedPassword()));

        return sprintf(
            '%s://%s:%s@%s:%d',
            $scheme,
            $username,
            $password,
            $settings->host(),
            $settings->port(),
        );
    }
}
