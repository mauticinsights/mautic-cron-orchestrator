<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Repository;

use DateTimeImmutable;
use Doctrine\ORM\EntityRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;

/**
 * @extends EntityRepository<CronJob>
 */
class CronJobRepository extends EntityRepository
{
    /**
     * @return CronJob[]
     */
    public function findEnabled(): array
    {
        return $this->findBy(['isEnabled' => true], ['id' => 'ASC']);
    }

    /**
     * @return CronJob[]
     */
    public function findStuck(int $timeoutMinutes): array
    {
        $threshold = new DateTimeImmutable("-{$timeoutMinutes} minutes");

        return $this->createQueryBuilder('j')
            ->where('j.lastRunStatus = :status')
            ->andWhere('j.lastRunAt IS NOT NULL')
            ->andWhere('j.lastRunAt < :threshold')
            ->setParameter('status', 'running')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->getResult();
    }

    /**
     * @return CronJob[]
     */
    public function findByPreset(string $preset): array
    {
        return $this->findBy(['preset' => $preset]);
    }

    public function deleteByPreset(string $preset): void
    {
        $this->createQueryBuilder('j')
            ->delete()
            ->where('j.preset = :preset')
            ->setParameter('preset', $preset)
            ->getQuery()
            ->execute();
    }

    /**
     * Remove jobs created by any built-in preset (minimal / standard / full).
     * Custom jobs are left untouched.
     */
    public function deleteManagedPresets(): void
    {
        $this->createQueryBuilder('j')
            ->delete()
            ->where('j.preset IN (:presets)')
            ->setParameter('presets', ['minimal', 'standard', 'full'])
            ->getQuery()
            ->execute();
    }

    public function save(CronJob $job): void
    {
        $this->getEntityManager()->persist($job);
        $this->getEntityManager()->flush();
    }

    public function delete(CronJob $job): void
    {
        $this->getEntityManager()->remove($job);
        $this->getEntityManager()->flush();
    }
}
