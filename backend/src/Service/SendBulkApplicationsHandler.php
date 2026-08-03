<?php

declare(strict_types=1);

namespace App\Service;

use App\Command\Application\RetryFailedApplicationsCommand;
use App\Command\Application\SendAllApplicationsCommand;
use App\Command\Application\SendBulkApplicationsCommand;
use App\DTO\QueuedSendResult;
use App\Repository\JobApplicationRepositoryInterface;
use App\Service\MailerConfiguration;
use App\Command\Message\SendApplicationMessage;
use Symfony\Component\Messenger\MessageBusInterface;

final readonly class SendBulkApplicationsHandler
{
    public function __construct(
        private JobApplicationRepositoryInterface $jobApplications,
        private MailerConfiguration $configuration,
        private MessageBusInterface $messageBus,
    ) {
    }

    public function selected(SendBulkApplicationsCommand $command): QueuedSendResult
    {
        $limit = $this->configuration->maxBatchSize;
        $items = $this->jobApplications->findByIds(array_slice($command->ids, 0, $limit), $limit);

        return $this->dispatch($items, $limit);
    }

    public function all(SendAllApplicationsCommand $command): QueuedSendResult
    {
        return $this->dispatch(
            $this->jobApplications->findPendingForSending($this->configuration->maxBatchSize),
            $this->configuration->maxBatchSize,
        );
    }

    public function retryFailed(RetryFailedApplicationsCommand $command): QueuedSendResult
    {
        return $this->dispatch(
            $this->jobApplications->findFailedForRetry($this->configuration->maxBatchSize),
            $this->configuration->maxBatchSize,
        );
    }

    /**
     * @param list<object> $items
     */
    private function dispatch(array $items, int $limit): QueuedSendResult
    {
        $queuedIds = [];

        foreach ($items as $jobApplication) {
            $id = $jobApplication->id()->toRfc4122();
            $this->messageBus->dispatch(new SendApplicationMessage($id));
            $queuedIds[] = $id;
        }

        return new QueuedSendResult(count($queuedIds), $limit, $queuedIds);
    }
}
