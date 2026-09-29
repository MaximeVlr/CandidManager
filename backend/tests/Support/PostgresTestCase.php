<?php

declare(strict_types=1);

namespace App\Tests\Support;

use App\Entity\Enum\ResponseStatus;
use App\Entity\JobApplication;
use App\Kernel;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Tester\ApplicationTester;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

abstract class PostgresTestCase extends KernelTestCase
{
    protected Connection $connection;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        TestDatabase::create();
        self::bootKernel(['environment' => 'test', 'debug' => true]);
        self::assertSame('test', self::$kernel->getEnvironment());
        $this->entityManager = self::getContainer()->get(EntityManagerInterface::class);
        $this->connection = $this->entityManager->getConnection();
        TestDatabase::reset($this->connection);

        if ($this->migrateOnSetUp()) {
            $this->console('doctrine:migrations:migrate');
        }
    }

    protected function migrateOnSetUp(): bool
    {
        return true;
    }

    protected function console(string $command, array $arguments = []): string
    {
        // Each CLI invocation gets its own kernel and dispatches console events.
        $kernel = new Kernel('test', true);
        $application = new Application($kernel);
        $application->setAutoExit(false);
        $tester = new ApplicationTester($application);
        try {
            $exitCode = $tester->run(['command' => $command, '--no-interaction' => true] + $arguments, ['interactive' => false]);
            self::assertSame(0, $exitCode, $tester->getDisplay());

            return $tester->getDisplay();
        } finally {
            $kernel->shutdown();
        }
    }

    protected function request(string $method, string $uri, array|string|null $body = null): Response
    {
        $request = Request::create($uri, $method, server: ['CONTENT_TYPE' => 'application/json'], content:
            is_array($body) ? json_encode($body, JSON_THROW_ON_ERROR) : $body);
        $response = self::$kernel->handle($request);
        self::$kernel->terminate($request, $response);

        return $response;
    }

    protected function json(Response $response, int $status = 200): array
    {
        self::assertSame($status, $response->getStatusCode(), substr($response->getContent(), 0, 1500));

        return json_decode($response->getContent(), true, 512, JSON_THROW_ON_ERROR);
    }

    protected function application(array $overrides = []): JobApplication
    {
        $data = self::payload($overrides);
        $application = new JobApplication(
            $data['company'], $data['location'], $data['email'], $data['subject'],
            $data['custom_message'], $data['official_source_url'], $data['follow_up'],
        );
        $application->updateDetails(
            $data['company'], $data['location'], $data['email'], $data['subject'],
            $data['custom_message'], $data['official_source_url'],
            ResponseStatus::from($data['response']), $data['follow_up'],
        );
        if ($data['sent']) {
            $application->markAsAlreadySent();
        }
        $this->entityManager->persist($application);
        $this->entityManager->flush();

        return $application;
    }

    protected static function payload(array $overrides = []): array
    {
        return array_replace([
            'company' => 'Acme', 'location' => 'Paris', 'email' => 'jobs@acme.example',
            'subject' => 'Candidature PHP', 'custom_message' => "Bonjour,\nUne candidature avec \"guillemets\".",
            'official_source_url' => 'https://acme.example/jobs?team=backend',
            'sent' => false, 'response' => 'none', 'follow_up' => false,
        ], $overrides);
    }
}
