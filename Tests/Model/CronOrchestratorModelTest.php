<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Model;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class CronOrchestratorModelTest extends TestCase
{
    private ?string $fakeProjectDir = null;

    protected function tearDown(): void
    {
        if (null !== $this->fakeProjectDir && is_dir($this->fakeProjectDir)) {
            @unlink($this->fakeProjectDir.'/bin/console');
            @rmdir($this->fakeProjectDir.'/bin');
            @rmdir($this->fakeProjectDir);
        }
        parent::tearDown();
    }

    public function testIsDueReturnsTrueWhenNeverRun(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(30);
        $job->setLastRunAt(null);

        $this->assertTrue($this->createModel()->isDue($job, new DateTimeImmutable('2026-07-18 12:00:00')));
    }

    public function testIsDueReturnsTrueWhenPastFrequency(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(30);
        $job->setLastRunAt(new DateTimeImmutable('2026-07-18 11:00:00'));

        $this->assertTrue($this->createModel()->isDue($job, new DateTimeImmutable('2026-07-18 12:00:00')));
    }

    public function testIsDueReturnsFalseWhenNotYetDue(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(60);
        $job->setLastRunAt(new DateTimeImmutable('2026-07-18 11:30:00'));

        $this->assertFalse($this->createModel()->isDue($job, new DateTimeImmutable('2026-07-18 11:45:00')));
    }

    public function testIsDueReturnsTrueAtExactBoundary(): void
    {
        $job = new CronJob();
        $job->setFrequencyMinutes(15);
        $job->setLastRunAt(new DateTimeImmutable('2026-07-18 11:00:00'));

        $this->assertTrue($this->createModel()->isDue($job, new DateTimeImmutable('2026-07-18 11:15:00')));
    }

    public function testGetPresetJobsReturnsMinimalSet(): void
    {
        $jobs = CronOrchestratorModel::getPresetJobs('minimal');
        $this->assertCount(5, $jobs);
        $this->assertSame('mautic:segments:update', $jobs[0]['command']);
        $this->assertSame('mautic:campaigns:update', $jobs[1]['command']);
        $this->assertSame('mautic:custom-field:create-column', $jobs[4]['command']);
        $this->assertSame('--no-interaction --no-ansi', $jobs[0]['arguments']);
    }

    public function testGetPresetJobsReturnsStandardSet(): void
    {
        $this->assertCount(10, CronOrchestratorModel::getPresetJobs('standard'));
    }

    public function testGetPresetJobsReturnsFullSet(): void
    {
        $jobs = CronOrchestratorModel::getPresetJobs('full');
        $this->assertCount(17, $jobs);
        $commands = array_column($jobs, 'command');
        $this->assertContains('mautic:maintenance:cleanup', $commands);
        $this->assertContains('mautic:donotsell:download', $commands);
        $this->assertContains('mautic:max-mind:purge', $commands);
        $this->assertNotContains('mautic:audit_log:cleanup', $commands);
    }

    public function testGetPresetJobsFallsBackToStandard(): void
    {
        $this->assertCount(10, CronOrchestratorModel::getPresetJobs('unknown'));
    }

    public function testSettersUpdateTimeoutAndRetention(): void
    {
        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())
            ->method('deleteOlderThan')
            ->with($this->callback(function (DateTimeInterface $dt): bool {
                $expected = new DateTimeImmutable('-7 days');

                return abs($dt->getTimestamp() - $expected->getTimestamp()) < 3;
            }))
            ->willReturn(0);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $this->createMock(CronJobRepository::class)],
            [CronJobLog::class, $logRepo],
        ]);

        $model = new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30);
        $model->setTimeoutMinutes(90);
        $model->setLogRetentionDays(7);
        $model->deleteOldLogs();
    }

    public function testDeleteOldLogsDelegatesToRepository(): void
    {
        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('deleteOlderThan')->willReturn(5);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $this->createMock(CronJobRepository::class)],
            [CronJobLog::class, $logRepo],
        ]);

        $this->assertSame(5, (new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30))->deleteOldLogs());
    }

    public function testRunJobNowSucceedsForValidCommand(): void
    {
        $projectDir = $this->createFakeConsoleProject(0);
        $job = new CronJob();
        $job->setName('test');
        $job->setCommand('mautic:segments:update');
        $job->setArguments('--no-interaction');
        $job->setFrequencyMinutes(15);

        $repo = $this->createMock(CronJobRepository::class);
        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');
        $repo->expects($this->atLeastOnce())->method('save');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);

        $result = (new CronOrchestratorModel($em, new NullLogger(), $projectDir, 60, 30))->runJobNow($job);
        $this->assertSame('success', $result['status']);
        $this->assertSame('success', $job->getLastRunStatus());
        $this->assertNotNull($job->getLastDurationSeconds());
        $this->assertGreaterThanOrEqual(0.0, $job->getLastDurationSeconds());
    }

    public function testRunJobNowReportsErrorWhenCommandFails(): void
    {
        $projectDir = $this->createFakeConsoleProject(1);
        $job = new CronJob();
        $job->setName('test');
        $job->setCommand('mautic:fail');
        $job->setFrequencyMinutes(15);

        $repo = $this->createMock(CronJobRepository::class);
        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);

        $result = (new CronOrchestratorModel($em, new NullLogger(), $projectDir, 60, 30))->runJobNow($job);
        $this->assertSame('error', $result['status']);
        $this->assertSame('error', $job->getLastRunStatus());
    }

    public function testSeedPresetCreatesJobs(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $repo = $this->createMock(CronJobRepository::class);
        $repo->expects($this->once())->method('deleteManagedPresets');
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $this->createMock(CronJobLogRepository::class)],
        ]);
        $em->expects($this->exactly(5))->method('persist');
        $em->expects($this->once())->method('flush');

        $created = (new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30))->seedPreset('minimal');
        $this->assertCount(5, $created);
        $this->assertSame('Segment updates', $created[0]->getName());
        $this->assertSame('minimal', $created[0]->getPreset());
        $this->assertSame('--no-interaction --no-ansi', $created[0]->getArguments());
    }

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
        $em->method('getRepository')->with(CronJob::class)->willReturn($repo);

        $due = (new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30))->findDueJobs();
        $this->assertCount(1, $due);
        $this->assertSame('due', $due[0]->getName());
    }

    public function testRunDueJobsResetsStuckJobs(): void
    {
        $now = new DateTimeImmutable('now');
        $projectDir = $this->createFakeConsoleProject(0);

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
        $repo->method('find')->willReturn($dueJob);
        $repo->expects($this->atLeast(2))->method('save');

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');
        $logRepo->method('deleteOlderThan')->willReturn(0);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);
        $em->expects($this->once())->method('clear');

        $stats = (new CronOrchestratorModel($em, new NullLogger(), $projectDir, 60, 30))->runDueJobs();
        $this->assertSame(1, $stats['run']);
        $this->assertSame(0, $stats['errors']);
        $this->assertSame('timeout', $stuckJob->getLastRunStatus());
    }

    public function testRunDueJobsSkipsJobsThatAreNotDue(): void
    {
        $now = new DateTimeImmutable('now');
        $notDue = new CronJob();
        $notDue->setName('not-due');
        $notDue->setCommand('mautic:skip');
        $notDue->setFrequencyMinutes(60);
        $notDue->setLastRunAt($now->modify('-5 minutes'));

        $repo = $this->createMock(CronJobRepository::class);
        $repo->method('findEnabled')->willReturn([$notDue]);
        $repo->method('findStuck')->willReturn([]);
        $repo->expects($this->never())->method('find');

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->method('deleteOlderThan')->willReturn(0);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);
        $em->expects($this->never())->method('clear');

        $stats = (new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30))->runDueJobs();
        $this->assertSame(['run' => 0, 'skipped' => 1, 'errors' => 0], $stats);
    }

    public function testRunDueJobsSkipsWhenFreshJobMissing(): void
    {
        $now = new DateTimeImmutable('now');
        $dueJob = new CronJob();
        $dueJob->setName('gone');
        $dueJob->setCommand('mautic:gone');
        $dueJob->setFrequencyMinutes(5);
        $dueJob->setLastRunAt($now->modify('-10 minutes'));

        $repo = $this->createMock(CronJobRepository::class);
        $repo->method('findEnabled')->willReturn([$dueJob]);
        $repo->method('findStuck')->willReturn([]);
        $repo->method('find')->willReturn(null);

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->method('deleteOlderThan')->willReturn(0);
        $logRepo->expects($this->never())->method('save');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);
        $em->expects($this->once())->method('clear');

        $stats = (new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30))->runDueJobs();
        $this->assertSame(0, $stats['run']);
        $this->assertSame(0, $stats['skipped']);
    }

    public function testRunDueJobsSkipsWhenAlreadyRunning(): void
    {
        $now = new DateTimeImmutable('now');
        $dueJob = new CronJob();
        $dueJob->setName('busy');
        $dueJob->setCommand('mautic:busy');
        $dueJob->setFrequencyMinutes(5);
        $dueJob->setLastRunAt($now->modify('-10 minutes'));

        $running = new CronJob();
        $running->setName('busy');
        $running->setCommand('mautic:busy');
        $running->setFrequencyMinutes(5);
        $running->setLastRunStatus('running');

        $repo = $this->createMock(CronJobRepository::class);
        $repo->method('findEnabled')->willReturn([$dueJob]);
        $repo->method('findStuck')->willReturn([]);
        $repo->method('find')->willReturn($running);

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->method('deleteOlderThan')->willReturn(0);
        $logRepo->expects($this->never())->method('save');

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);
        $em->expects($this->once())->method('clear');

        $stats = (new CronOrchestratorModel($em, new NullLogger(), '/tmp', 60, 30))->runDueJobs();
        $this->assertSame(1, $stats['skipped']);
        $this->assertSame(0, $stats['run']);
    }

    public function testRunDueJobsLogsCleanupWhenRowsDeleted(): void
    {
        $projectDir = $this->createFakeConsoleProject(0);
        $now = new DateTimeImmutable('now');
        $dueJob = new CronJob();
        $dueJob->setName('due');
        $dueJob->setCommand('mautic:due');
        $dueJob->setFrequencyMinutes(5);
        $dueJob->setLastRunAt($now->modify('-10 minutes'));

        $repo = $this->createMock(CronJobRepository::class);
        $repo->method('findEnabled')->willReturn([$dueJob]);
        $repo->method('findStuck')->willReturn([]);
        $repo->method('find')->willReturn($dueJob);

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');
        $logRepo->method('deleteOlderThan')->willReturn(4);

        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects($this->once())->method('info')->with(
            'Cron orchestrator: cleaned up {count} old log rows',
            ['count' => 4]
        );

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);

        $stats = (new CronOrchestratorModel($em, $logger, $projectDir, 60, 30))->runDueJobs();
        $this->assertSame(1, $stats['run']);
    }

    public function testRunDueJobsTriggersLogCleanup(): void
    {
        $projectDir = $this->createFakeConsoleProject(0);
        $dueJob = new CronJob();
        $dueJob->setName('due');
        $dueJob->setCommand('mautic:due:cmd');
        $dueJob->setFrequencyMinutes(5);
        $dueJob->setLastRunAt((new DateTimeImmutable())->modify('-10 minutes'));

        $repo = $this->createMock(CronJobRepository::class);
        $repo->method('findEnabled')->willReturn([$dueJob]);
        $repo->method('findStuck')->willReturn([]);
        $repo->method('find')->willReturn($dueJob);
        $repo->expects($this->atLeastOnce())->method('save');

        $logRepo = $this->createMock(CronJobLogRepository::class);
        $logRepo->expects($this->once())->method('save');
        $logRepo->method('deleteOlderThan')->willReturn(0);

        $em = $this->createMock(EntityManagerInterface::class);
        $em->method('getRepository')->willReturnMap([
            [CronJob::class, $repo],
            [CronJobLog::class, $logRepo],
        ]);
        $em->expects($this->once())->method('clear');

        $stats = (new CronOrchestratorModel($em, new NullLogger(), $projectDir, 60, 30))->runDueJobs();
        $this->assertSame(1, $stats['run']);
    }

    private function createModel(): CronOrchestratorModel
    {
        return new CronOrchestratorModel(
            $this->createMock(EntityManagerInterface::class),
            new NullLogger(),
            '/tmp',
            60,
            30
        );
    }

    private function createFakeConsoleProject(int $exitCode): string
    {
        $dir = sys_get_temp_dir().'/cron-orch-'.uniqid('', true);
        mkdir($dir.'/bin', 0777, true);
        file_put_contents(
            $dir.'/bin/console',
            "#!/usr/bin/env php\n<?php\nfwrite(STDOUT, \"ok\\n\");\nexit({$exitCode});\n"
        );
        chmod($dir.'/bin/console', 0755);
        $this->fakeProjectDir = $dir;

        return $dir;
    }
}
