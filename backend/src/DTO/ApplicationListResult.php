<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\JobApplication;

final readonly class ApplicationListResult
{
    /**
     * @param list<JobApplication> $items
     */
    public function __construct(
        public array $items,
        public int $total,
        public int $page,
        public int $limit,
    ) {
    }

    /**
     * @return array{items: list<array<string, mixed>>, total: int, page: int, limit: int, pages: int}
     */
    public function toArray(): array
    {
        return [
            'items' => array_map(
                static fn (JobApplication $jobApplication): array => $jobApplication->toArray(),
                $this->items,
            ),
            'total' => $this->total,
            'page' => $this->page,
            'limit' => $this->limit,
            'pages' => (int) ceil($this->total / $this->limit),
        ];
    }
}
