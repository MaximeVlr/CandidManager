<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\MailTemplate;
use App\Service\CvAttachmentStorage;
use App\Tests\Support\PostgresTestCase;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\File\UploadedFile;

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

    public function testPreviewStreamsAssociatedCvInline(): void
    {
        $template = new MailTemplate('custom', '<p>Test</p>', 'Test');
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $contents = "%PDF-1.4\n1 0 obj\n<<>>\nendobj\n%%EOF\n";
        $temporaryPath = tempnam(sys_get_temp_dir(), 'cv-preview-');
        self::assertNotFalse($temporaryPath);
        file_put_contents($temporaryPath, $contents);
        $storage = self::getContainer()->get(CvAttachmentStorage::class);
        $storedFile = $storage->store(new UploadedFile($temporaryPath, 'CV été.pdf', test: true));
        $path = $storage->absolutePath($storedFile['stored_name']);

        try {
            $template->attachCv($storedFile['original_name'], $storedFile['stored_name'], $storedFile['mime_type']);
            $this->entityManager->flush();

            $response = $this->request('GET', '/api/templates/custom/cv/preview');
            self::assertInstanceOf(BinaryFileResponse::class, $response);
            self::assertSame(200, $response->getStatusCode());
            self::assertSame('application/pdf', $response->headers->get('Content-Type'));
            self::assertStringStartsWith('inline;', $response->headers->get('Content-Disposition'));
            self::assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
            self::assertSame($contents, file_get_contents($response->getFile()->getPathname()));

            unlink($path);
            $this->json($this->request('GET', '/api/templates/custom/cv/preview'), 404);
        } finally {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }

    public function testMissingTemplateOrCvReturnsNotFound(): void
    {
        $this->json($this->request('GET', '/api/templates/unknown/cv/preview'), 404);
        $this->entityManager->persist(new MailTemplate('without-cv', '<p>Test</p>', 'Test'));
        $this->entityManager->flush();
        $this->json($this->request('GET', '/api/templates/without-cv/cv/preview'), 404);
    }
}
