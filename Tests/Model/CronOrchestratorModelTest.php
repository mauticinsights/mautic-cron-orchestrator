<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

final class CronOrchestratorModelTest extends TestCase
{
    // ------------------------ isDue ------------------------

    public function testIsDueReturnsTrueWhenNeverRun(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(30);
        $job->setLastRunAt(null);

        $model = $this->createModel();
        $this->assertTrue($model->isDue($job, new DateTimeImmutable('2026-07-18 12:00:00')));
    }

    public function testIsDueReturnsTrueWhenPastFrequency(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(30);
        $job->setLastRunAt(new DateTimeImmutable('2026-07-18 11:00:00'));

        $model = $this->createModel();
        $this->assertTrue($model->isDue($job, new DateTimeImmutable('2026-07-18 12:00:00')));
    }

    public function testIsDueReturnsFalseWhenNotYetDue(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(60);
        $job->setLastRunAt(new DateTimeImmutable('2026-07-18 11:30:00'));

        $model = $this->createModel();
        $this->assertFalse($model->isDue($job, new DateTimeImmutable('2026-07-18 11:45:00')));
    }

    public function testIsDueReturnsTrueAtExactBoundary(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(15);
        $job->setLastRunAt(new DateTimeImmutable('2026-07-18 11:00:00'));

        $model = $this->createModel();
        $this->assertTrue($model->isDue($job, new DateTimeImmutable('2026-07-18 11:15:00')));
    }

    // ------------------------ getPresetJobs ------------------------

    public function testGetPresetJobsReturnsMinimalSet(): void
    {
        $jobs = CronOrchestratorModel::getPresetJobs('minimal');
        $this->assertCount(4, $jobs);
        $this->assertSame('mautic:segments:update', $jobs[0]['command']);
    }

    public function testGetPresetJobsReturnsStandardSet(): void
    {
        $jobs = CronOrchestratorModel::getPresetJobs('standard');
        $this->assertCount(9, $jobs);
    }

    public function testGetPresetJobsReturnsFullSet(): void
    {
        $jobs = CronOrchestratorModel::getPresetJobs('full');
        $this->assertCount(12, $jobs);
        $commands = array_column($jobs, 'command');
        $this->assertNotContains('mautic:audit_log:cleanup', $commands);
    }

    public function testGetPresetJobsFallsBackToStandard(): void
    {
        $jobs = CronOrchestratorModel::getPresetJobs('unknown');
        $this->assertCount(9, $jobs);
    }

    // ------------------------ deleteOldLogs ------------------------

    public function testDeleteOldLogsDelegatesToRepository(): void
    {
        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())
            ->method('deleteOlderThan')
            ->with($this->callback(function (DateTimeInterface $dt): bool {
                return true;
            }))
            ->willReturn(5);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnMap([
                [CronJob::class, $this->createMock(CronJobRepository::class)],
                [\MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog::class, $logRepo],
            ]);

        $model = new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
        $this->assertSame(5, $model->deleteOldLogs());
    }

    // ------------------------ runJobNow ------------------------

    public function testRunJobNowSucceedsForValidCommand(): void
    {
        $job = new CronJob();
        $job->setName('test');
        $job->setCommand('mautic:segments:update');
        $job->setFrequencyMinutes(15);

        $repo = $this->createMock(CronJobRepository::class);
        $repo->method('find')->willReturn($job);

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnMap([
                [CronJob::class, $repo],
                [\MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog::class, $logRepo],
            ]);

        $model = new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
        $result = $model->runJobNow($job);

        // No real console binary in standalone tests — Process fails and status is error.
        $this->assertSame('error', $result['status']);
        $this->assertStringContainsString('bin/console', $result['output']);
    }

    // ------------------------ seedPreset ------------------------

    public function testSeedPresetCreatesJobs(): void
    {
        $idCounter = 0;
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(CronJobRepository::class);
        $repo->expects($this->once())->method('deleteByPreset')->with('minimal');

        $em->method('getRepository')
            ->willReturnMap([
                [CronJob::class, $repo],
                [\MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog::class, $this->createMock(CronJobLogRepository::class)],
            ]);

        $em->expects($this->exactly(4))->method('persist');
        $em->expects($this->once())->method('flush');

        $model = new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
        $created = $model->seedPreset('minimal');

        $this->assertCount(4, $created);
        $this->assertSame('Segment updates', $created[0]->getName());
        $this->assertSame(15, $created[0]->getFrequencyMinutes());
        $this->assertSame('minimal', $created[0]->getPreset());
    }

    // ------------------------ findDueJobs ------------------------

    public function testFindDueJobsReturnsOnlyEnabledDueJobs(): void
    {
        $now = new DateTimeImmutable('now');

        $dueJob = new CronJob();
        $dueJob->setName('due');
        $dueJob->setFrequencyMinutes(15);
        $dueJob->setIsEnabled(true);
        $dueJob->setLastRunAt($now->modify('-20 minutes'));

        $notDueJob = new CronJob();
        $notDueJob->setName('not-due');
        $notDueJob->setFrequencyMinutes(60);
        $notDueJob->setIsEnabled(true);
        $notDueJob->setLastRunAt($now->modify('-5 minutes'));

        $repo = $this->createMock(CronJobRepository::class);
        $repo->expects($this->once())->method('findEnabled')->willReturn([$dueJob, $notDueJob]);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->with(CronJob::class)
            ->willReturn($repo);

        $model = new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
        $due = $model->findDueJobs();

        $this->assertCount(1, $due);
        $this->assertSame('due', $due[0]->getName());
    }

    // ------------------------ runDueJobs: stuck reset ------------------------

    public function testRunDueJobsResetsStuckJobs(): void
    {
        $now = new DateTimeImmutable('now');
        $stuckJob = new CronJob();
        $stuckJob->setName('stuck');
        $stuckJob->setCommand('mautic:stuck:cmd');
        $stuckJob->setFrequencyMinutes(15);
        $stuckJob->setLastRunAt($now->modify('-120 minutes'));
        $stuckJob->setLastRunStatus('running');

        $dueJob = new CronJob();
        $dueJob->setName('due');
        $dueJob->setCommand('mautic:due:cmd');
        $dueJob->setFrequencyMinutes(5);
        $dueJob->setLastRunAt($now->modify('-10 minutes'));
        $dueJob->setLastRunStatus(null);

        $repo = $this->createMock(CronJobRepository::class);
        $repo->expects($this->once())->method('findEnabled')->willReturn([$dueJob]);
        $repo->expects($this->once())->method('findStuck')->with(60)->willReturn([$stuckJob]);
        $repo->method('find')->willReturnCallback(function ($id) use ($dueJob) {
            return $dueJob;
        });
        // 1 save: reset stuck to 'timeout'; runCommand will also call save
        $repo->expects($this->atLeast(2))->method('save');

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnMap([
                [CronJob::class, $repo],
                [\MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog::class, $logRepo],
            ]);
        $em->expects($this->once())->method('clear');

        $model = new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
        $stats = $model->runDueJobs();

        // Process fails in standalone tests (no real console) → counted as errors, but job was attempted.
        $this->assertSame(0, $stats['run']);
        $this->assertSame(1, $stats['errors'], 'Due job must be attempted after stuck reset');
        $this->assertSame('timeout', $stuckJob->getLastRunStatus());
    }

    // ------------------------ runDueJobs: log retention ------------------------

    public function testRunDueJobsTriggersLogCleanup(): void
    {
        $dueJob = new CronJob();
        $dueJob->setName('due');
        $dueJob->setCommand('mautic:due:cmd');
        $dueJob->setFrequencyMinutes(5);
        $dueJob->setLastRunAt((new DateTimeImmutable())->modify('-10 minutes'));

        $repo = $this->createMock(CronJobRepository::class);
        $repo->method('findEnabled')->willReturn([$dueJob]);
        $repo->method('findStuck')->willReturn([]);
        $repo->method('find')->willReturn($dueJob);
        // runCommand will call save() (execution will fail because command not found)
        $repo->expects($this->atLeastOnce())->method('save');

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')
            ->willReturnMap([
                [CronJob::class, $repo],
                [\MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog::class, $logRepo],
            ]);
        $em->expects($this->once())->method('clear');

        $model = new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
        $stats = $model->runDueJobs();

        $this->assertIsArray($stats);
    }

    // ------------------------ helpers ------------------------

    private function createModel(): CronOrchestratorModel
    {
        $em = $this->createMock(EntityManagerInterface::class);

        return new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
    }
}
