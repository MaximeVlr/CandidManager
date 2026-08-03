<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum SendStatus: string
{
    case Pending = 'pending';
    case Sending = 'sending';
    case Sent = 'sent';
    case Failed = 'failed';
}
