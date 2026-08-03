<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\ResponseStatus;
use App\Entity\Enum\SendStatus;
use App\Repository\DoctrineJobApplicationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineJobApplicationRepository::class)]
#[ORM\Table(name: 'job_applications')]
#[ORM\UniqueConstraint(name: 'uniq_job_applications_email_company', columns: ['email', 'company'])]
class JobApplication
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 180)]
    private string $company;

    #[ORM\Column(length: 120)]
    private string $location;

    #[ORM\Column(length: 180)]
    private string $email;

    #[ORM\Column(length: 180)]
    private string $subject;

    #[ORM\Column(type: 'text')]
    private string $customMessage;

    #[ORM\Column(type: 'text')]
    private string $officialSourceUrl;

    #[ORM\Column(enumType: SendStatus::class)]
    private SendStatus $sendStatus = SendStatus::Pending;

    #[ORM\Column(enumType: ResponseStatus::class)]
    private ResponseStatus $responseStatus = ResponseStatus::None;

    #[ORM\Column]
    private bool $followUp = false;

    #[ORM\Column]
    private int $followUpCount = 0;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $sentAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $lastError = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    /** @var Collection<int, ApplicationSendLog> */
    #[ORM\OneToMany(mappedBy: 'jobApplication', targetEntity: ApplicationSendLog::class, cascade: ['persist'], orphanRemoval: true)]
    private Collection $sendLogs;

    public function __construct(
        string $company,
        string $location,
        string $email,
        string $subject,
        string $customMessage,
        string $officialSourceUrl,
        bool $followUp = false,
    ) {
        $now = new \DateTimeImmutable();

        $this->id = Uuid::v7();
        $this->company = $company;
        $this->location = $location;
        $this->email = $email;
        $this->subject = $subject;
        $this->customMessage = $customMessage;
        $this->officialSourceUrl = $officialSourceUrl;
        $this->followUp = $followUp;
        $this->createdAt = $now;
        $this->updatedAt = $now;
        $this->sendLogs = new ArrayCollection();
    }

    public function id(): Uuid
    {
        return $this->id;
    }

    /**
     * @return array{
     *   id: string,
     *   company: string,
     *   location: string,
     *   email: string,
     *   subject: string,
     *   custom_message: string,
     *   official_source_url: string,
     *   send_status: string,
     *   response: string,
     *   follow_up: bool,
     *   follow_up_count: int,
     *   sent_at: string|null,
     *   last_error: string|null,
     *   created_at: string,
     *   updated_at: string
     * }
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id->toRfc4122(),
            'company' => $this->company,
            'location' => $this->location,
            'email' => $this->email,
            'subject' => $this->subject,
            'custom_message' => $this->customMessage,
            'official_source_url' => $this->officialSourceUrl,
            'send_status' => $this->sendStatus->value,
            'response' => $this->responseStatus->value,
            'follow_up' => $this->followUp,
            'follow_up_count' => $this->followUpCount,
            'sent_at' => $this->sentAt?->format(\DateTimeInterface::ATOM),
            'last_error' => $this->lastError,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }

    /**
     * @return array<string, string>
     */
    public function templateVariables(): array
    {
        return [
            'company' => $this->company,
            'location' => $this->location,
            'email' => $this->email,
            'subject' => $this->subject,
            'custom_message' => $this->customMessage,
            'official_source_url' => $this->officialSourceUrl,
        ];
    }

    public function markAsSending(): void
    {
        $this->sendStatus = SendStatus::Sending;
        $this->touch();
    }

    public function markAsSent(): void
    {
        $this->sendStatus = SendStatus::Sent;
        $this->sentAt ??= new \DateTimeImmutable();
        $this->lastError = null;
        $this->touch();
    }

    public function markAsAlreadySent(): void
    {
        $this->markAsSent();
    }

    public function incrementFollowUpCount(): void
    {
        ++$this->followUpCount;
        $this->followUp = true;
        $this->touch();
    }

    public function markAsFailed(string $error): void
    {
        $this->sendStatus = SendStatus::Failed;
        $this->lastError = $error;
        $this->touch();
    }

    public function updateDetails(
        string $company,
        string $location,
        string $email,
        string $subject,
        string $customMessage,
        string $officialSourceUrl,
        ResponseStatus $responseStatus,
        bool $followUp,
    ): void {
        $this->company = $company;
        $this->location = $location;
        $this->email = $email;
        $this->subject = $subject;
        $this->customMessage = $customMessage;
        $this->officialSourceUrl = $officialSourceUrl;
        $this->responseStatus = $responseStatus;
        $this->followUp = $followUp;
        $this->touch();
    }

    public function addSendLog(ApplicationSendLog $sendLog): void
    {
        if (!$this->sendLogs->contains($sendLog)) {
            $this->sendLogs->add($sendLog);
        }
    }

    private function touch(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }
}
