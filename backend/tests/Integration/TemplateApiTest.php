<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Tests\Support\PostgresTestCase;

final class TemplateApiTest extends PostgresTestCase
{
    public function testCrudWithCategoryAndExistingPutBehavior(): void
    {
        $category = $this->json($this->request('POST', '/api/template-categories', ['name' => 'Candidatures']), 201);
        $created = $this->json($this->request('POST', '/api/templates', [
            'name' => 'custom',
            'html_body' => '<p>{{ company }}</p>',
            'text_body' => '{{ company }}',
            'category_id' => $category['id'],
        ]), 201);
        self::assertSame('custom', $created['name']);
        self::assertSame($category['id'], $created['category_id']);
        self::assertSame($created, $this->json($this->request('GET', '/api/templates/custom')));
        self::assertSame(['custom'], array_column($this->json($this->request('GET', '/api/templates'))['items'], 'name'));

        $updated = $this->json($this->request('PUT', '/api/templates/custom', [
            'html_body' => '<p>Updated</p>',
            'text_body' => 'Updated',
        ]));
        self::assertSame('Updated', $updated['text_body']);
        self::assertSame($category['id'], $updated['category_id']);

        $uncategorized = $this->json($this->request('PUT', '/api/templates/custom', [
            'html_body' => '<p>Updated</p>',
            'text_body' => 'Updated',
            'category_id' => null,
        ]));
        self::assertNull($uncategorized['category_id']);
        self::assertNull($this->connection->fetchOne('SELECT category_id FROM mail_templates WHERE name = ?', ['custom']));

        $default = $this->json($this->request('PUT', '/api/templates/default', [
            'html_body' => '<p>Default</p>',
            'text_body' => 'Default',
        ]));
        self::assertSame('default', $default['name']);

        self::assertSame(204, $this->request('DELETE', '/api/templates/custom')->getStatusCode());
        $this->json($this->request('GET', '/api/templates/custom'), 404);
        self::assertSame(['default'], array_column($this->json($this->request('GET', '/api/templates'))['items'], 'name'));
    }

    public function testInvalidAndDuplicateTemplatesAreRejected(): void
    {
        $payload = ['name' => 'custom', 'html_body' => '<p>Valid</p>', 'text_body' => 'Valid'];
        $this->json($this->request('POST', '/api/templates', $payload), 201);
        $this->json($this->request('POST', '/api/templates', $payload), 409);

        foreach (['not json', '[]', '{}'] as $body) {
            $this->json($this->request('POST', '/api/templates', $body), 422);
        }
        $this->json($this->request('POST', '/api/templates', array_replace($payload, ['name' => 'invalid-category', 'category_id' => 'invalid'])), 422);
        $this->json($this->request('POST', '/api/templates', array_replace($payload, ['name' => 'missing-category', 'category_id' => '00000000-0000-0000-0000-000000000001'])), 422);
        $this->json($this->request('POST', '/api/templates', array_replace($payload, ['name' => 'bad', 'text_body' => '{{ unknown }}'])), 422);
        $this->json($this->request('PUT', '/api/templates/custom', ['html_body' => 'valid', 'text_body' => '']), 422);
        $this->json($this->request('DELETE', '/api/templates/unknown'), 404);
        self::assertSame('Valid', $this->json($this->request('GET', '/api/templates/custom'))['text_body']);
    }
}
