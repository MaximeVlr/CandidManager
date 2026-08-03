<?php

declare(strict_types=1);

namespace App\Query;

final readonly class ListSendLogsQuery
{
    public function __construct(
        public ?string $search,
        public ?string $status,
        public int $page,
        public int $limit,
    ) {
    }
}
