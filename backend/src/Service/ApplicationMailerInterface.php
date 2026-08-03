<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\JobApplication;
use App\Entity\MailTemplate;

interface ApplicationMailerInterface
{
    public function send(JobApplication $jobApplication, MailTemplate $mailTemplate): void;
}
