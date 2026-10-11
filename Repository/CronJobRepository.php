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

    /**
     * Active built-in preset currently seeded into the job table, if any.
     */
    public function findActivePreset(): ?string
    {
        $job = $this->createQueryBuilder('j')
            ->where('j.preset IN (:presets)')
            ->setParameter('presets', ['minimal', 'standard', 'full'])
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();

        return $job instanceof CronJob ? $job->getPreset() : null;
    }

    /**
     * @return CronJob[]
     */
    public function search(string $search = ''): array
    {
        $qb = $this->createQueryBuilder('j')->orderBy('j.id', 'ASC');

        $search = trim($search);
        if ('' !== $search) {
            $qb->andWhere('j.name LIKE :q OR j.command LIKE :q')
                ->setParameter('q', '%'.$search.'%');
        }

        return $qb->getQuery()->getResult();
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

    /**
     * Remove every cron job (used by the empty Custom preset).
     */
    public function deleteAll(): void
    {
        $this->createQueryBuilder('j')
            ->delete()
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

    /**
     * @param int[] $ids
     */
    public function deleteByIds(array $ids): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ([] === $ids) {
            return 0;
        }

        $jobs = $this->findBy(['id' => $ids]);
        $em   = $this->getEntityManager();
        foreach ($jobs as $job) {
            $em->remove($job);
        }
        $em->flush();

        return count($jobs);
    }

    /**
     * @param int[] $ids
     */
    public function setEnabledByIds(array $ids, bool $enabled): int
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids))));
        if ([] === $ids) {
            return 0;
        }

        return (int) $this->createQueryBuilder('j')
            ->update()
            ->set('j.isEnabled', ':enabled')
            ->set('j.modifiedAt', ':modifiedAt')
            ->where('j.id IN (:ids)')
            ->setParameter('enabled', $enabled)
            ->setParameter('modifiedAt', new DateTimeImmutable())
            ->setParameter('ids', $ids)
            ->getQuery()
            ->execute();
    }
}
