<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\EventSubscriber\Exception\InvalidApplicationsJsonException;
use App\Service\ApplicationJsonValidator;
use App\Service\WebUrlValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class ApplicationJsonValidatorTest extends TestCase
{
    public function testValidInputIsNormalized(): void
    {
        $json = json_encode(['applications' => [[
            'company' => ' Acme ', 'location' => ' Paris ', 'email' => 'jobs@acme.example',
            'subject' => ' Application ', 'custom_message' => ' Hello ',
            'official_source_url' => ' https://acme.example/job offers ',
            'sent' => true, 'response' => 'positive', 'follow_up' => true,
        ]]], JSON_THROW_ON_ERROR);
        $items = (new ApplicationJsonValidator(new WebUrlValidator()))->validate($json);
        self::assertCount(1, $items);
        self::assertSame('Acme', $items[0]->company);
        self::assertSame('Paris', $items[0]->location);
        self::assertSame('Application', $items[0]->subject);
        self::assertSame('Hello', $items[0]->customMessage);
        self::assertSame('https://acme.example/job%20offers', $items[0]->officialSourceUrl);
        self::assertSame('positive', $items[0]->response);
        self::assertTrue($items[0]->sent);
        self::assertTrue($items[0]->followUp);
    }

    #[DataProvider('invalidPayloads')]
    public function testInvalidInputProducesStructuredErrors(string $json, ?string $field): void
    {
        try {
            (new ApplicationJsonValidator(new WebUrlValidator()))->validate($json);
            self::fail('Invalid input was accepted.');
        } catch (InvalidApplicationsJsonException $exception) {
            self::assertContains($field, array_column($exception->errors(), 'field'));
        }
    }

    public static function invalidPayloads(): iterable
    {
        $valid = [
            'company' => 'Acme', 'location' => 'Paris', 'email' => 'jobs@acme.example',
            'subject' => 'Application', 'custom_message' => 'Hello',
            'official_source_url' => 'https://acme.example/jobs',
            'sent' => false, 'response' => 'none', 'follow_up' => false,
        ];
        yield 'malformed JSON' => ['{', null];
        yield 'scalar root' => ['null', null];
        yield 'missing applications' => ['{}', 'applications'];
        foreach (['response' => 'unknown', 'sent' => 'false', 'follow_up' => 1, 'email' => 'invalid', 'official_source_url' => 'javascript:alert(1)', 'company' => ''] as $field => $value) {
            yield $field => [json_encode(['applications' => [array_replace($valid, [$field => $value])]], JSON_THROW_ON_ERROR), $field];
        }
        foreach (['response' => null, 'response number' => 42] as $label => $value) {
            yield $label.' type' => [json_encode(['applications' => [array_replace($valid, ['response' => $value])]], JSON_THROW_ON_ERROR), 'response'];
        }
        $missing = $valid;
        unset($missing['response']);
        yield 'missing response' => [json_encode(['applications' => [$missing]], JSON_THROW_ON_ERROR), 'response'];
        yield 'duplicate recipient' => [json_encode(['applications' => [$valid, array_replace($valid, ['email' => 'JOBS@acme.example'])]], JSON_THROW_ON_ERROR), 'email'];
    }
}
