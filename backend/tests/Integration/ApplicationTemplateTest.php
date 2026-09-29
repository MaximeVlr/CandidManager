<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\MailTemplate;
use App\Tests\Support\PostgresTestCase;
use App\Tests\Support\RecordingMailer;
use Symfony\Component\Uid\Uuid;

final class ApplicationTemplateTest extends PostgresTestCase
{
    public function testSelectedTemplateIsPersistedPreviewedAndSent(): void
    {
        $template = new MailTemplate('custom', '<p>Custom {{ company }}</p>', 'Custom {{ company }}');
        $this->entityManager->persist($template);
        $this->entityManager->flush();

        $application = $this->json($this->request('POST', '/api/applications', self::payload([
            'template_id' => $template->id()->toRfc4122(),
        ])), 201);
        self::assertSame($template->id()->toRfc4122(), $application['template_id']);
        self::assertSame($template->id()->toRfc4122(), $this->connection->fetchOne('SELECT template_id FROM job_applications WHERE id = ?', [$application['id']]));
        self::assertSame('<p>Custom Acme</p>', $this->json($this->request('GET', '/api/applications/'.$application['id'].'/preview'))['html_body']);

        $this->json($this->request('POST', '/api/send/'.$application['id']));
        self::assertSame(['custom'], self::getContainer()->get(RecordingMailer::class)->templates);
    }

    public function testEditingCanKeepChangeAndClearTemplate(): void
    {
        $first = new MailTemplate('first', '<p>First</p>', 'First');
        $second = new MailTemplate('second', '<p>Second</p>', 'Second');
        $this->entityManager->persist($first);
        $this->entityManager->persist($second);
        $this->entityManager->flush();

        $application = $this->json($this->request('POST', '/api/applications', self::payload([
            'template_id' => $first->id()->toRfc4122(),
        ])), 201);
        $id = $application['id'];
        self::assertSame($first->id()->toRfc4122(), $this->json($this->request('PATCH', '/api/applications/'.$id, self::payload(['subject' => 'Updated'])))['template_id']);
        self::assertSame($second->id()->toRfc4122(), $this->json($this->request('PATCH', '/api/applications/'.$id, self::payload([
            'template_id' => $second->id()->toRfc4122(),
        ])))['template_id']);
        self::assertSame('Second', $this->json($this->request('GET', '/api/applications/'.$id.'/preview'))['text_body']);

        self::assertNull($this->json($this->request('PATCH', '/api/applications/'.$id, self::payload(['template_id' => null])))['template_id']);
        self::assertNull($this->connection->fetchOne('SELECT template_id FROM job_applications WHERE id = ?', [$id]));
    }

    public function testInvalidOrUnknownTemplateIsRejectedAndDeletionClearsAssignment(): void
    {
        $payload = self::payload();
        $this->json($this->request('POST', '/api/applications', array_replace($payload, ['template_id' => 'invalid'])), 422);
        $this->json($this->request('POST', '/api/applications', array_replace($payload, ['template_id' => Uuid::v7()->toRfc4122()])), 422);
        self::assertSame(0, (int) $this->connection->fetchOne('SELECT COUNT(*) FROM job_applications'));

        $template = new MailTemplate('custom', '<p>Custom</p>', 'Custom');
        $this->entityManager->persist($template);
        $this->entityManager->flush();
        $application = $this->json($this->request('POST', '/api/applications', array_replace($payload, [
            'template_id' => $template->id()->toRfc4122(),
        ])), 201);
        $this->json($this->request('PATCH', '/api/applications/'.$application['id'], array_replace($payload, [
            'template_id' => Uuid::v7()->toRfc4122(),
        ])), 422);
        self::assertSame($template->id()->toRfc4122(), $this->connection->fetchOne('SELECT template_id FROM job_applications'));

        self::assertSame(204, $this->request('DELETE', '/api/templates/custom')->getStatusCode());
        self::assertNull($this->connection->fetchOne('SELECT template_id FROM job_applications'));
        $this->entityManager->clear();
        self::assertNull($this->json($this->request('GET', '/api/applications/'.$application['id']))['template_id']);
    }
}
