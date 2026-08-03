<?php

declare(strict_types=1);

namespace App\EventSubscriber\Exception;

final class MailSendingDisabledException extends \RuntimeException
{
    public function __construct()
    {
        parent::__construct('Real email sending is disabled by configuration.');
    }
}
