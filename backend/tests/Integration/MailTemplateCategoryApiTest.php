<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\MailTemplate;
use App\Entity\MailTemplateCategory;
use App\Tests\Support\PostgresTestCase;
use Symfony\Component\Uid\Uuid;

final class MailTemplateCategoryApiTest extends PostgresTestCase
{
    public function testCrudAndDeletingCategoryKeepsItsTemplates(): void
    {
        $first = $this->json($this->request('POST', '/api/template-categories', ['name' => '  Suivi  ']), 201);
        $second = $this->json($this->request('POST', '/api/template-categories', ['name' => 'Candidature']), 201);
        self::assertSame('Suivi', $first['name']);
        self::assertTrue(Uuid::isValid($first['id']));

        $list = $this->json($this->request('GET', '/api/template-categories'));
        self::assertSame(['Candidature', 'Suivi'], array_column($list['items'], 'name'));
        self::assertSame($first, $this->json($this->request('GET', '/api/template-categories/'.$first['id'])));

        $updated = $this->json($this->request('PUT', '/api/template-categories/'.$first['id'], ['name' => 'Relance']));
        self::assertSame(['id' => $first['id'], 'name' => 'Relance'], $updated);
        self::assertSame('Relance', $this->connection->fetchOne('SELECT name FROM mail_template_categories WHERE id = ?', [$first['id']]));

        $category = $this->entityManager->find(MailTemplateCategory::class, $first['id']);
        $template = new MailTemplate('test', '<p>Test</p>', 'Test');
        $template->setCategory($category);
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        self::assertSame(204, $this->request('DELETE', '/api/template-categories/'.$first['id'])->getStatusCode());
        self::assertNull($this->connection->fetchOne('SELECT category_id FROM mail_templates WHERE name = ?', ['test']));
        self::assertSame('test', $this->connection->fetchOne('SELECT name FROM mail_templates WHERE name = ?', ['test']));
        $this->json($this->request('GET', '/api/template-categories/'.$first['id']), 404);
        self::assertSame([$second], $this->json($this->request('GET', '/api/template-categories'))['items']);
    }

    public function testInvalidNamesAndDuplicateNamesAreRejected(): void
    {
        foreach (['not json', '[]', '{}', '{"name":null}', '{"name":"  "}'] as $body) {
            $this->json($this->request('POST', '/api/template-categories', $body), 422);
        }
        $this->json($this->request('POST', '/api/template-categories', ['name' => str_repeat('a', 121)]), 422);

        $created = $this->json($this->request('POST', '/api/template-categories', ['name' => 'Suivi']), 201);
        $other = $this->json($this->request('POST', '/api/template-categories', ['name' => 'Autre']), 201);
        $this->json($this->request('POST', '/api/template-categories', ['name' => ' Suivi ']), 409);
        $this->json($this->request('PATCH', '/api/template-categories/'.$other['id'], ['name' => 'Suivi']), 409);
        self::assertSame($other, $this->json($this->request('GET', '/api/template-categories/'.$other['id'])));
        self::assertSame($created, $this->json($this->request('PATCH', '/api/template-categories/'.$created['id'], ['name' => 'Suivi'])));

        $this->json($this->request('GET', '/api/template-categories/invalid'), 400);
        $this->json($this->request('DELETE', '/api/template-categories/'.Uuid::v7()), 404);
    }
}
