<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DoctrineMailTemplateRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineMailTemplateRepository::class)]
#[ORM\Table(name: 'mail_templates')]
class MailTemplate
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 120, unique: true)]
    private string $name;

    #[ORM\Column(type: 'text')]
    private string $htmlBody;

    #[ORM\Column(type: 'text')]
    private string $textBody;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvOriginalName = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $cvStoredName = null;

    #[ORM\Column(length: 120, nullable: true)]
    private ?string $cvMimeType = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct(string $name, string $htmlBody, string $textBody)
    {
        $now = new \DateTimeImmutable();

        $this->id = Uuid::v7();
        $this->name = $name;
        $this->htmlBody = $htmlBody;
        $this->textBody = $textBody;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function updateBodies(string $htmlBody, string $textBody): void
    {
        $this->htmlBody = $htmlBody;
        $this->textBody = $textBody;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function htmlBody(): string
    {
        return $this->htmlBody;
    }

    public function textBody(): string
    {
        return $this->textBody;
    }

    public function attachCv(string $originalName, string $storedName, string $mimeType): void
    {
        $this->cvOriginalName = $originalName;
        $this->cvStoredName = $storedName;
        $this->cvMimeType = $mimeType;
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function cvStoredName(): ?string
    {
        return $this->cvStoredName;
    }

    public function cvOriginalName(): ?string
    {
        return $this->cvOriginalName;
    }

    public function cvMimeType(): ?string
    {
        return $this->cvMimeType;
    }

    /**
     * @return array{id: string, name: string, html_body: string, text_body: string, cv: array{original_name: string, mime_type: string}|null, created_at: string, updated_at: string}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toRfc4122(),
            'name' => $this->name,
            'html_body' => $this->htmlBody,
            'text_body' => $this->textBody,
            'cv' => $this->cvOriginalName === null ? null : [
                'original_name' => $this->cvOriginalName,
                'mime_type' => $this->cvMimeType ?? 'application/octet-stream',
            ],
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
