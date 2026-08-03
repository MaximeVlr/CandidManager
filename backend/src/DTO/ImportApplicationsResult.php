<?php

declare(strict_types=1);

namespace App\DTO;

final readonly class ImportApplicationsResult
{
    /**
     * @param list<array{index: int|null, field: string|null, message: string}> $errors
     */
    public function __construct(
        public int $created,
        public int $skipped,
        public array $errors,
    ) {
    }

    /**
     * @return array{created: int, skipped: int, errors: list<array{index: int|null, field: string|null, message: string}>}
     */
    public function toArray(): array
    {
        return [
            'created' => $this->created,
            'skipped' => $this->skipped,
            'errors' => $this->errors,
        ];
    }
}
