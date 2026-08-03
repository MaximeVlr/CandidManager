<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class QueuedSendResult
{
    /**
     * @param list<string> $queuedIds
     */
    public function __construct(
        public int $queued,
        public int $limit,
        public array $queuedIds,
    ) {
    }

    /**
     * @return array{queued: int, limit: int, queued_ids: list<string>}
     */
    public function toArray(): array
    {
        return [
            'queued' => $this->queued,
            'limit' => $this->limit,
            'queued_ids' => $this->queuedIds,
        ];
    }
}
