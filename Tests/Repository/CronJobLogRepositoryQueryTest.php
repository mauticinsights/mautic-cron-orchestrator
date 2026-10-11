<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Repository;

use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Covers last-log lookup used by the status-column modal.
 */
final class CronJobLogRepositoryQueryTest extends TestCase
{
    public function testFindRecentByJobOrdersByStartedAtDescending(): void
    {
        $job = new CronJob();
        $log = new CronJobLog();
        $log->setCronJob($job);

        /** @var CronJobLogRepository&MockObject $repo */
        $repo = $this->getMockBuilder(CronJobLogRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['findBy'])
            ->getMock();

        $repo->expects($this->once())
            ->method('findBy')
            ->with(
                ['cronJob' => $job],
                ['startedAt' => 'DESC'],
                1
            )
            ->willReturn([$log]);

        $this->assertSame([$log], $repo->findRecentByJob($job, 1));
    }
}
