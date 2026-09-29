<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Support\PostgresTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Component\Uid\Uuid;

final class ApplicationApiTest extends PostgresTestCase
{
    #[DataProvider('exportStates')]
    public function testJsonExportImportPreservesTheExportedFields(string $response, bool $sent, bool $followUp): void
    {
        $application = $this->application(['response' => $response, 'sent' => $sent, 'follow_up' => $followUp]);
        $export = $this->request('GET', '/api/export.json');
        $data = $this->json($export);
        self::assertSame([self::payload(['response' => $response, 'sent' => $sent, 'follow_up' => $followUp])], $data['applications']);
        self::assertSame(204, $this->request('DELETE', '/api/applications/'.$application->id())->getStatusCode());

        $import = $this->json($this->request('POST', '/api/import', $export->getContent()), 201);
        self::assertSame(['created' => 1, 'skipped' => 0, 'errors' => []], $import);
        self::assertSame($data, $this->json($this->request('GET', '/api/export.json')));
    }

    public static function exportStates(): iterable
    {
        yield 'pending' => ['none', false, false];
        yield 'positive and sent' => ['positive', true, false];
        yield 'negative with follow up' => ['negative', true, true];
        yield 'awaiting response' => ['pending', false, true];
    }

    public function testImportSkipsExistingRowsAndContinuesWithNewRows(): void
    {
        $this->application();
        $payload = ['applications' => [self::payload(), self::payload(['company' => 'Beta']), self::payload(['company' => 'Gamma'])]];
        $first = $this->json($this->request('POST', '/api/import', $payload), 201);
        self::assertSame(2, $first['created']);
        self::assertSame(1, $first['skipped']);
        self::assertSame(0, $first['errors'][0]['index']);
        $second = $this->json($this->request('POST', '/api/import', $payload), 201);
        self::assertSame(0, $second['created']);
        self::assertSame(3, $second['skipped']);
        self::assertSame(3, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM job_applications'));
    }

    #[DataProvider('duplicateChanges')]
    public function testPatchDuplicateReturnsConflictWithoutChangingStoredData(array $initial, array $changes): void
    {
        $first = $this->application();
        $second = $this->application($initial);
        $before = $this->connection->fetchAllAssociative('SELECT * FROM job_applications ORDER BY id');
        $response = $this->request('PATCH', '/api/applications/'.$second->id(), self::payload($changes));
        self::assertSame(409, $response->getStatusCode(), substr($response->getContent(), 0, 1500));
        self::assertArrayHasKey('message', $this->json($response, 409));
        self::assertSame($before, $this->connection->fetchAllAssociative('SELECT * FROM job_applications ORDER BY id'));
        self::assertNotSame($first->id()->toRfc4122(), $second->id()->toRfc4122());
    }

    public static function duplicateChanges(): iterable
    {
        yield 'company' => [['company' => 'Beta'], []];
        yield 'email' => [['email' => 'other@acme.example'], []];
        yield 'both' => [['company' => 'Beta', 'email' => 'other@acme.example'], []];
    }

    public function testPatchCanKeepItsOwnUniqueKey(): void
    {
        $application = $this->application();
        $data = $this->json($this->request('PATCH', '/api/applications/'.$application->id(), self::payload(['subject' => 'Updated', 'response' => 'positive'])));
        self::assertSame('Updated', $data['subject']);
        self::assertSame('positive', $data['response']);
        self::assertSame('Updated', $this->connection->fetchOne('SELECT subject FROM job_applications'));
    }

    public function testPatchUnknownApplicationReturnsNotFound(): void
    {
        $this->json($this->request('PATCH', '/api/applications/'.Uuid::v7(), self::payload()), 404);
    }

    public function testExportFiltersAndLimitAreApplied(): void
    {
        $this->application();
        $this->application(['company' => 'Beta', 'sent' => true, 'response' => 'positive']);
        $this->application(['company' => 'Gamma', 'sent' => true, 'response' => 'negative']);
        $data = $this->json($this->request('GET', '/api/export.json?send_status=sent&response=positive'));
        self::assertSame(['Beta'], array_column($data['applications'], 'company'));
        $data = $this->json($this->request('GET', '/api/export.json?sort=company&direction=desc&limit=1'));
        self::assertSame(['Gamma'], array_column($data['applications'], 'company'));
    }

    public function testInvalidImportDoesNotPartiallyPersistRows(): void
    {
        $response = $this->json($this->request('POST', '/api/import', ['applications' => [
            self::payload(), self::payload(['company' => 'Beta', 'response' => 'invalid']),
        ]]), 422);
        self::assertSame('response', $response['errors'][0]['field']);
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM job_applications'));
    }
}
