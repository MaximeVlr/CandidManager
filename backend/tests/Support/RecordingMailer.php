<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\JobApplication;
use App\Entity\MailTemplate;
use App\Service\ApplicationMailerInterface;
use Symfony\Component\Mailer\Exception\TransportException;

final class RecordingMailer implements ApplicationMailerInterface
{
    public int $attempts = 0;
    public int $failuresRemaining = 0;
    public string $error = '451 Temporary SMTP failure';
    public array $sent = [];
    public array $templates = [];

    public function send(JobApplication $jobApplication, MailTemplate $mailTemplate): void
    {
        ++$this->attempts;
        if ($this->failuresRemaining > 0) {
            --$this->failuresRemaining;
            throw new TransportException($this->error);
        }

        $this->sent[] = $jobApplication->id()->toRfc4122();
        $this->templates[] = $mailTemplate->toArray()['name'];
    }
}
