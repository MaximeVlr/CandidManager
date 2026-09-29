<?php

declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\MailTemplate;
use App\Entity\MailTemplateCategory;
use App\Tests\Support\PostgresTestCase;

final class MailTemplateCategoryTest extends PostgresTestCase
{
    public function testCategoryCanContainSeveralTemplatesAndCanBeRemoved(): void
    {
        $category = new MailTemplateCategory('Candidatures');
        $first = new MailTemplate('first', '<p>First</p>', 'First');
        $second = new MailTemplate('second', '<p>Second</p>', 'Second');
        $category->addMailTemplate($first);
        $second->setCategory($category);

        self::assertCount(2, $category->mailTemplates());
        $this->entityManager->persist($category);
        $this->entityManager->persist($first);
        $this->entityManager->persist($second);
        $this->entityManager->flush();
        $this->entityManager->clear();

        $savedCategory = $this->entityManager->find(MailTemplateCategory::class, $category->id());
        self::assertNotNull($savedCategory);
        self::assertCount(2, $savedCategory->mailTemplates());

        $savedFirst = $this->entityManager->find(MailTemplate::class, $first->toArray()['id']);
        self::assertSame($savedCategory, $savedFirst->category());
        $savedCategory->removeMailTemplate($savedFirst);
        self::assertNull($savedFirst->category());
        self::assertCount(1, $savedCategory->mailTemplates());
        $this->entityManager->flush();

        self::assertNull($this->connection->fetchOne('SELECT category_id FROM mail_templates WHERE name = ?', ['first']));
    }
}
