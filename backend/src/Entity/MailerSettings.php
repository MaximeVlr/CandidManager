<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\DoctrineMailerSettingsRepository;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Uuid;

#[ORM\Entity(repositoryClass: DoctrineMailerSettingsRepository::class)]
#[ORM\Table(name: 'mailer_settings')]
class MailerSettings
{
    #[ORM\Id]
    #[ORM\Column(type: 'uuid')]
    private Uuid $id;

    #[ORM\Column(length: 40)]
    private string $provider = 'gmail';

    #[ORM\Column]
    private bool $enabled = false;

    #[ORM\Column(length: 180)]
    private string $fromEmail = '';

    #[ORM\Column(length: 120)]
    private string $fromName = 'CandidManager';

    #[ORM\Column(length: 180)]
    private string $username = '';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $encryptedPassword = null;

    #[ORM\Column(length: 180)]
    private string $host = 'smtp.gmail.com';

    #[ORM\Column]
    private int $port = 587;

    #[ORM\Column(length: 20)]
    private string $encryption = 'tls';

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();

        $this->id = Uuid::v7();
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    public function update(
        string $provider,
        bool $enabled,
        string $fromEmail,
        string $fromName,
        string $username,
        ?string $encryptedPassword,
        string $host,
        int $port,
        string $encryption,
    ): void {
        $this->provider = $provider;
        $this->enabled = $enabled;
        $this->fromEmail = $fromEmail;
        $this->fromName = $fromName;
        $this->username = $username;
        $this->host = $host;
        $this->port = $port;
        $this->encryption = $encryption;

        if ($encryptedPassword !== null) {
            $this->encryptedPassword = $encryptedPassword;
        }

        $this->updatedAt = new \DateTimeImmutable();
    }

    public function enabled(): bool
    {
        return $this->enabled;
    }

    public function provider(): string
    {
        return $this->provider;
    }

    public function fromEmail(): string
    {
        return $this->fromEmail;
    }

    public function fromName(): string
    {
        return $this->fromName;
    }

    public function username(): string
    {
        return $this->username;
    }

    public function encryptedPassword(): ?string
    {
        return $this->encryptedPassword;
    }

    public function host(): string
    {
        return $this->host;
    }

    public function port(): int
    {
        return $this->port;
    }

    public function encryption(): string
    {
        return $this->encryption;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'enabled' => $this->enabled,
            'from_email' => $this->fromEmail,
            'from_name' => $this->fromName,
            'username' => $this->username,
            'has_password' => $this->encryptedPassword !== null,
            'host' => $this->host,
            'port' => $this->port,
            'encryption' => $this->encryption,
            'updated_at' => $this->updatedAt->format(\DateTimeInterface::ATOM),
        ];
    }
}
