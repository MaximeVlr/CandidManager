<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DoctrineMailTemplateCategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineMailTemplateCategoryRepository::class)]
#[ORM\Table(name: 'mail_template_categories')]
class MailTemplateCategory
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 120, unique: true)]
    private string $name;

    /** @var Collection<int, MailTemplate> */
    #[ORM\OneToMany(mappedBy: 'category', targetEntity: MailTemplate::class)]
    private Collection $mailTemplates;

    public function __construct(string $name)
    {
        $this->id = Uuid::v7();
        $this->name = $name;
        $this->mailTemplates = new ArrayCollection();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function rename(string $name): void
    {
        $this->name = $name;
    }

    /** @return array{id: string, name: string} */
    public function toArray(): array
    {
        return ['id' => $this->id->toRfc4122(), 'name' => $this->name];
    }

    /** @return Collection<int, MailTemplate> */
    public function mailTemplates(): Collection
    {
        return $this->mailTemplates;
    }

    public function addMailTemplate(MailTemplate $mailTemplate): void
    {
        if (!$this->mailTemplates->contains($mailTemplate)) {
            $this->mailTemplates->add($mailTemplate);
        }

        if ($mailTemplate->category() !== $this) {
            $mailTemplate->setCategory($this);
        }
    }

    public function removeMailTemplate(MailTemplate $mailTemplate): void
    {
        $this->mailTemplates->removeElement($mailTemplate);

        if ($mailTemplate->category() === $this) {
            $mailTemplate->setCategory(null);
        }
    }
}
