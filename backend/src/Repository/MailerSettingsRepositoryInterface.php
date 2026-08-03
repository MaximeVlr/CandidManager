<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailerSettings;

interface MailerSettingsRepositoryInterface
{
    public function get(): MailerSettings;

    public function save(MailerSettings $settings): void;
}
