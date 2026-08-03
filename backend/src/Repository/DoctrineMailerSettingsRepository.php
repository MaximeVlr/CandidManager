<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\MailerSettings;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MailerSettings>
 */
final class DoctrineMailerSettingsRepository extends ServiceEntityRepository implements MailerSettingsRepositoryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MailerSettings::class);
    }

    public function get(): MailerSettings
    {
        $settings = $this->findOneBy([]);

        if ($settings !== null) {
            return $settings;
        }

        $settings = new MailerSettings();
        $this->save($settings);

        return $settings;
    }

    public function save(MailerSettings $settings): void
    {
        $this->getEntityManager()->persist($settings);
        $this->getEntityManager()->flush();
    }
}
