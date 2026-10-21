<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Support\PostgresTestCase;
use PHPUnit\Framework\Attributes\DataProvider;

final class ApplicationArchiveTest extends PostgresTestCase
{
    public function testEditingCanArchiveKeepAndUnarchiveApplication(): void
    {
        $application = $this->json($this->request('POST', '/api/applications', self::payload()), 201);
        self::assertFalse($application['archived']);
        $url = '/api/applications/'.$application['id'];

        self::assertTrue($this->json($this->request('PATCH', $url, self::payload(['archived' => true])))['archived']);
        $this->entityManager->clear();
        self::assertTrue($this->json($this->request('GET', $url))['archived']);

        self::assertTrue($this->json($this->request('PATCH', $url, self::payload(['subject' => 'Updated'])))['archived']);
        self::assertSame('Updated', $this->json($this->request('GET', $url))['subject']);

        self::assertFalse($this->json($this->request('PATCH', $url, self::payload(['archived' => false])))['archived']);
        $this->entityManager->clear();
        self::assertFalse($this->json($this->request('GET', $url))['archived']);
    }

    public function testApplicationCanBeCreatedArchived(): void
    {
        $application = $this->json($this->request('POST', '/api/applications', self::payload(['archived' => true])), 201);
        $this->entityManager->clear();
        self::assertTrue($this->json($this->request('GET', '/api/applications/'.$application['id']))['archived']);
    }

    #[DataProvider('invalidArchiveValues')]
    public function testInvalidArchiveValueIsRejectedWithoutChangingApplication(mixed $value): void
    {
        $application = $this->application();
        $url = '/api/applications/'.$application->id()->toRfc4122();
        $response = $this->json($this->request('PATCH', $url, self::payload(['archived' => $value, 'subject' => 'Invalid update'])), 422);

        self::assertSame('archived', $response['errors'][0]['field']);
        $this->entityManager->clear();
        $stored = $this->json($this->request('GET', $url));
        self::assertFalse($stored['archived']);
        self::assertSame(self::payload()['subject'], $stored['subject']);
    }

    public static function invalidArchiveValues(): iterable
    {
        yield 'null' => [null];
        yield 'string' => ['false'];
        yield 'integer' => [1];
        yield 'array' => [[]];
    }
}
