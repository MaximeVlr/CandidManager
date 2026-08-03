<?php

declare(strict_types=1);

namespace App\Entity;

use App\Entity\Enum\SendLogStatus;
use App\Repository\DoctrineApplicationSendLogRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineApplicationSendLogRepository::class)]
#[ORM\Table(name: 'application_send_logs')]
class ApplicationSendLog
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\ManyToOne(targetEntity: JobApplication::class, inversedBy: 'sendLogs')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private JobApplication $jobApplication;

    #[ORM\Column(enumType: SendLogStatus::class)]
    private SendLogStatus $status;

    #[ORM\Column(length: 80, nullable: true)]
    private ?string $smtpCode;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $errorMessage;

    #[ORM\Column(nullable: true)]
    private ?int $durationMs;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct(
        JobApplication $jobApplication,
        SendLogStatus $status,
        ?string $smtpCode = null,
        ?string $errorMessage = null,
        ?int $durationMs = null,
    ) {
        $this->id = Uuid::v7();
        $this->jobApplication = $jobApplication;
        $this->status = $status;
        $this->smtpCode = $smtpCode;
        $this->errorMessage = $errorMessage;
        $this->durationMs = $durationMs;
        $this->createdAt = new \DateTimeImmutable();
    }

    /**
     * @return array{
     *   id: string,
     *   application: array{id: string, company: string, email: string},
     *   status: string,
     *   smtp_code: string|null,
     *   error_message: string|null,
     *   duration_ms: int|null,
     *   created_at: string
     * }
     */
    public function toArray(): array
    {
        $application = $this->jobApplication->toArray();

        return [
            'id' => $this->id->toRfc4122(),
            'application' => [
                'id' => $application['id'],
                'company' => $application['company'],
                'email' => $application['email'],
            ],
            'status' => $this->status->value,
            'smtp_code' => $this->smtpCode,
            'error_message' => $this->errorMessage,
            'duration_ms' => $this->durationMs,
            'created_at' => $this->createdAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
