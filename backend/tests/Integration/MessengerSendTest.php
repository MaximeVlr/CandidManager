<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Command\Message\SendApplicationMessage;
use App\Entity\MailTemplate;
use App\Tests\Support\PostgresTestCase;
use App\Tests\Support\RecordingMailer;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Worker;

final class MessengerSendTest extends PostgresTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager->persist(new MailTemplate('default', '<p>{{ custom_message }}</p>', '{{ custom_message }}'));
        $this->entityManager->persist(new MailTemplate('follow_up', '<p>Follow up</p>', 'Follow up'));
        $this->entityManager->flush();
    }

    public function testInitialDeliveryPersistsSuccess(): void
    {
        $application = $this->application();
        self::getContainer()->get(MessageBusInterface::class)->dispatch(new SendApplicationMessage($application->id()->toRfc4122()));
        self::assertSame(0, $this->mailer()->attempts);
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages'));
        $this->consume();

        self::assertSame(1, $this->mailer()->attempts);
        self::assertSame([$application->id()->toRfc4122()], $this->mailer()->sent);
        $stored = $this->connection->fetchAssociative('SELECT * FROM job_applications');
        self::assertSame('sent', $stored['send_status']);
        self::assertNotNull($stored['sent_at']);
        self::assertNull($stored['last_error']);
        $log = $this->connection->fetchAssociative('SELECT * FROM application_send_logs');
        self::assertSame('success', $log['status']);
        self::assertSame($application->id()->toRfc4122(), $log['job_application_id']);
        self::assertGreaterThanOrEqual(0, $log['duration_ms']);
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages'));
    }

    public function testSmtpFailurePersistsSanitizedDiagnostic(): void
    {
        $application = $this->application();
        $this->mailer()->failuresRemaining = 1;
        $this->mailer()->error = '451 Cannot connect to smtp://user:password@smtp.example.test';
        $response = $this->json($this->request('POST', '/api/send/'.$application->id()), 400);

        self::assertFalse($response['sent']);
        self::assertSame(1, $this->mailer()->attempts);
        self::assertSame([], $this->mailer()->sent);
        $stored = $this->connection->fetchAssociative('SELECT * FROM job_applications');
        self::assertSame('failed', $stored['send_status']);
        self::assertNull($stored['sent_at']);
        self::assertStringNotContainsString('password', $stored['last_error']);
        self::assertStringNotContainsString('password', $response['message']);
        $log = $this->connection->fetchAssociative('SELECT * FROM application_send_logs');
        self::assertSame('failure', $log['status']);
        self::assertSame('451', $log['smtp_code']);
        self::assertSame($stored['last_error'], $log['error_message']);
        self::assertGreaterThanOrEqual(0, $log['duration_ms']);
    }

    public function testRedeliverySendsOnlyOnce(): void
    {
        $application = $this->application();
        $message = new SendApplicationMessage($application->id()->toRfc4122());
        $bus = self::getContainer()->get(MessageBusInterface::class);
        $bus->dispatch($message);
        $this->consume();
        $this->entityManager->clear();
        $bus->dispatch($message);
        $this->consume();

        self::assertSame(1, $this->mailer()->attempts);
        self::assertSame([$message->applicationId], $this->mailer()->sent);
        self::assertSame(['success'], $this->connection->fetchFirstColumn('SELECT status FROM application_send_logs'));
        self::assertSame('sent', $this->connection->fetchOne('SELECT send_status FROM job_applications'));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages'));
    }

    public function testSmtpFailureIsRetriedThenSucceeds(): void
    {
        $application = $this->application();
        $this->mailer()->failuresRemaining = 1;
        $this->mailer()->error = '451 Cannot connect to smtp://user:password@smtp.example.test';
        self::getContainer()->get(MessageBusInterface::class)->dispatch(new SendApplicationMessage($application->id()->toRfc4122()));
        $this->consume();

        self::assertSame(2, $this->mailer()->attempts);
        self::assertCount(1, $this->mailer()->sent);
        $logs = $this->connection->fetchAllAssociative('SELECT * FROM application_send_logs ORDER BY id');
        self::assertSame(['failure', 'success'], array_column($logs, 'status'));
        self::assertSame('451', $logs[0]['smtp_code']);
        self::assertStringNotContainsString('password', $logs[0]['error_message']);
        self::assertSame('sent', $this->connection->fetchOne('SELECT send_status FROM job_applications'));
        self::assertNull($this->connection->fetchOne('SELECT last_error FROM job_applications'));
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages'));
    }

    public function testExhaustedSmtpRetriesReachTheFailureTransport(): void
    {
        $application = $this->application();
        $this->mailer()->failuresRemaining = 10;
        self::getContainer()->get(MessageBusInterface::class)->dispatch(new SendApplicationMessage($application->id()->toRfc4122()));
        $this->consume();

        self::assertSame(4, $this->mailer()->attempts);
        self::assertSame([], $this->mailer()->sent);
        self::assertSame(4, (int) $this->connection->fetchOne("SELECT COUNT(*) FROM application_send_logs WHERE status = 'failure'"));
        self::assertSame('failed', $this->connection->fetchOne('SELECT send_status FROM job_applications'));
        self::assertSame(['failed'], $this->connection->fetchFirstColumn('SELECT queue_name FROM messenger_messages'));
        $failed = iterator_to_array(self::getContainer()->get('messenger.transport.failed')->get());
        self::assertCount(1, $failed);
        self::assertSame($application->id()->toRfc4122(), $failed[0]->getMessage()->applicationId);
    }

    public function testExplicitFollowUpStillSendsAfterInitialDelivery(): void
    {
        $application = $this->application(['sent' => true]);
        $response = $this->json($this->request('POST', '/api/send/'.$application->id().'/follow-up'));
        self::assertTrue($response['sent']);
        self::assertSame(1, $response['application']['follow_up_count']);
        self::assertSame(['follow_up'], $this->mailer()->templates);
    }

    public function testRepeatedHttpSendDoesNotSendAgain(): void
    {
        $application = $this->application();
        $uri = '/api/send/'.$application->id();
        $this->json($this->request('POST', $uri));
        $this->request('POST', $uri);
        self::assertSame(1, $this->mailer()->attempts);
        self::assertSame(1, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM application_send_logs'));
    }

    public function testInvalidMessageDoesNotSendOrRetry(): void
    {
        self::getContainer()->get(MessageBusInterface::class)->dispatch(new SendApplicationMessage('not-a-uuid'));
        $this->consume();
        self::assertSame(0, $this->mailer()->attempts);
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM messenger_messages'));
    }

    private function mailer(): RecordingMailer
    {
        return self::getContainer()->get(RecordingMailer::class);
    }

    private function consume(): void
    {
        $dispatcher = self::getContainer()->get('event_dispatcher');
        $stopWhenIdle = static function (WorkerRunningEvent $event): void {
            if ($event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }
        };
        $dispatcher->addListener(WorkerRunningEvent::class, $stopWhenIdle);
        try {
            $worker = new Worker(
                ['async' => self::getContainer()->get('messenger.transport.async')],
                self::getContainer()->get('messenger.routable_message_bus'),
                $dispatcher,
            );
            $worker->run(['sleep' => 0, 'time_limit' => 5]);
        } finally {
            $dispatcher->removeListener(WorkerRunningEvent::class, $stopWhenIdle);
        }
    }
}
