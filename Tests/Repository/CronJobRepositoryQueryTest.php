<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Repository;

use Doctrine\ORM\AbstractQuery;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Unit-tests repository query helpers with mocked Doctrine collaborators.
 */
final class CronJobRepositoryQueryTest extends TestCase
{
    public function testSearchOrdersByNameAscending(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('orderBy')->with('j.name', 'ASC')->willReturnSelf();
        $qb->expects($this->never())->method('andWhere');
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->createRepoMock(['createQueryBuilder']);
        $repo->expects($this->once())->method('createQueryBuilder')->with('j')->willReturn($qb);

        $this->assertSame([], $repo->search(''));
    }

    public function testSearchAppliesNameAndCommandFilter(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->method('getResult')->willReturn([]);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->method('orderBy')->willReturnSelf();
        $qb->expects($this->once())
            ->method('andWhere')
            ->with('j.name LIKE :q OR j.command LIKE :q')
            ->willReturnSelf();
        $qb->expects($this->once())
            ->method('setParameter')
            ->with('q', '%segment%')
            ->willReturnSelf();
        $qb->method('getQuery')->willReturn($query);

        $repo = $this->createRepoMock(['createQueryBuilder']);
        $repo->method('createQueryBuilder')->willReturn($qb);

        $repo->search('  segment  ');
    }

    public function testDeleteByIdsReturnsZeroForEmptyOrInvalidIds(): void
    {
        $repo = $this->createRepoMock(['findBy', 'getEntityManager']);
        $repo->expects($this->never())->method('findBy');
        $repo->expects($this->never())->method('getEntityManager');

        $this->assertSame(0, $repo->deleteByIds([]));
        $this->assertSame(0, $repo->deleteByIds([0, '0', 'abc']));
    }

    public function testDeleteByIdsRemovesFoundJobsAndDeduplicates(): void
    {
        $jobA = new CronJob();
        $jobB = new CronJob();

        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects($this->exactly(2))->method('remove')->withConsecutive([$jobA], [$jobB]);
        $em->expects($this->once())->method('flush');

        $repo = $this->createRepoMock(['findBy', 'getEntityManager']);
        $repo->expects($this->once())
            ->method('findBy')
            ->with(['id' => [1, 2]])
            ->willReturn([$jobA, $jobB]);
        $repo->method('getEntityManager')->willReturn($em);

        $this->assertSame(2, $repo->deleteByIds([1, 2, 2, 1]));
    }

    public function testSetEnabledByIdsReturnsZeroForEmptyInput(): void
    {
        $repo = $this->createRepoMock(['createQueryBuilder']);
        $repo->expects($this->never())->method('createQueryBuilder');

        $this->assertSame(0, $repo->setEnabledByIds([], true));
        $this->assertSame(0, $repo->setEnabledByIds([0], false));
    }

    public function testSetEnabledByIdsUpdatesMatchingJobs(): void
    {
        $query = $this->createMock(AbstractQuery::class);
        $query->expects($this->once())->method('execute')->willReturn(3);

        $qb = $this->createMock(QueryBuilder::class);
        $qb->expects($this->once())->method('update')->willReturnSelf();
        $qb->expects($this->exactly(2))->method('set')->willReturnSelf();
        $qb->expects($this->once())->method('where')->with('j.id IN (:ids)')->willReturnSelf();
        $qb->expects($this->exactly(3))->method('setParameter')->willReturnSelf();
        $qb->expects($this->once())->method('getQuery')->willReturn($query);

        $repo = $this->createRepoMock(['createQueryBuilder']);
        $repo->expects($this->once())->method('createQueryBuilder')->with('j')->willReturn($qb);

        $this->assertSame(3, $repo->setEnabledByIds([10, 11, 10], false));
    }

    /**
     * @param list<string> $methods
     *
     * @return CronJobRepository&MockObject
     */
    private function createRepoMock(array $methods): CronJobRepository
    {
        return $this->getMockBuilder(CronJobRepository::class)
            ->disableOriginalConstructor()
            ->onlyMethods($methods)
            ->getMock();
    }
}
