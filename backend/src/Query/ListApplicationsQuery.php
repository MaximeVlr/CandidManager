<?php

declare(strict_types=1);

namespace App\Query;

final readonly class ListApplicationsQuery
{
    public function __construct(
        public ?string $search,
        public ?string $sendStatus,
        public ?string $response,
        public ?int $followUpCount,
        public string $sort,
        public string $direction,
        public int $page,
        public int $limit,
    ) {
    }
}
