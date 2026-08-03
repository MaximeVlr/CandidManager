<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum ResponseStatus: string
{
    case None = 'none';
    case Positive = 'positive';
    case Negative = 'negative';
    case Pending = 'pending';
}
