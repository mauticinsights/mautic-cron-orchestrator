<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Repository;

use DateTimeInterface;
use Doctrine\ORM\EntityRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog;

/**
 * @extends EntityRepository<CronJobLog>
 */
class CronJobLogRepository extends EntityRepository
{
    /**
     * @return CronJobLog[]
     */
    public function findRecentByJob(CronJob $job, int $limit = 10): array
    {
        return $this->findBy(
            ['cronJob' => $job],
            ['startedAt' => 'DESC'],
            $limit,
        );
    }

    public function deleteOlderThan(DateTimeInterface $threshold): int
    {
        return (int) $this->createQueryBuilder('l')
            ->delete()
            ->where('l.startedAt < :threshold')
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    public function save(CronJobLog $log): void
    {
        $this->getEntityManager()->persist($log);
        $this->getEntityManager()->flush();
    }
}
