<?php

declare(strict_types=1);

namespace App\Command\MessageHandler;

use App\Command\Application\SendApplicationCommand;
use App\Service\SendApplicationHandler;
use App\Command\Message\SendApplicationMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Uid\Uuid;

#[AsMessageHandler]
final readonly class SendApplicationMessageHandler
{
    public function __construct(
        private SendApplicationHandler $sendApplication,
    ) {
    }

    public function __invoke(SendApplicationMessage $message): void
    {
        if (!Uuid::isValid($message->applicationId)) {
            return;
        }

        ($this->sendApplication)(new SendApplicationCommand(Uuid::fromString($message->applicationId)));
    }
}
