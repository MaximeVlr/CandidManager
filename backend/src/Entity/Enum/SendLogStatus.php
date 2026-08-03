<?php

declare(strict_types=1);

namespace App\Entity\Enum;

enum SendLogStatus: string
{
    case Success = 'success';
    case Failure = 'failure';
}
