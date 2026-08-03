<?php

declare(strict_types=1);

namespace App\EventSubscriber\Exception;

final class InvalidApplicationsJsonException extends \RuntimeException
{
    /**
     * @param list<array{index: int|null, field: string|null, message: string}> $errors
     */
    public function __construct(
        private readonly array $errors,
    ) {
        parent::__construct('Invalid applications JSON.');
    }

    /**
     * @return list<array{index: int|null, field: string|null, message: string}>
     */
    public function errors(): array
    {
        return $this->errors;
    }
}
