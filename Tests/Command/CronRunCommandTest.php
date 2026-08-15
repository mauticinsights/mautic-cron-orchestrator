<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\MauticCronOrchestratorBundle\Command\CronRunCommand;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

final class CronRunCommandTest extends TestCase
{
    public function testDisabledOrchestratorSkipsWork(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->never())->method('runDueJobs');
        $model->expects($this->never())->method('findDueJobs');
        $model->expects($this->never())->method('deleteOldLogs');

        $command = new CronRunCommand($model, new CoreParametersHelper([
            'cron_orchestrator_enabled' => false,
        ]));
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('mautic:cron:run'));
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('disabled', $tester->getDisplay());
    }

    public function testDryRunListsDueJobsAndDoesNotExecute(): void
    {
        $dueJob = new CronJob();
        $dueJob->setName('Test job');
        $dueJob->setCommand('mautic:test:run');
        $dueJob->setFrequencyMinutes(15);

        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->once())->method('findDueJobs')->willReturn([$dueJob]);
        $model->expects($this->never())->method('runDueJobs');

        $command = new CronRunCommand($model, new CoreParametersHelper());
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('mautic:cron:run'));
        $exitCode = $tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('Dry-run', $display);
        $this->assertStringContainsString('mautic:test:run', $display);
        $this->assertStringContainsString('Test job', $display);
    }

    public function testDryRunWithNoDueJobsShowsMessage(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->once())->method('findDueJobs')->willReturn([]);
        $model->expects($this->never())->method('runDueJobs');

        $command = new CronRunCommand($model, new CoreParametersHelper());
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('mautic:cron:run'));
        $exitCode = $tester->execute(['--dry-run' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('No due jobs found', $tester->getDisplay());
    }

    public function testCleanupOnlyDoesNotRunJobs(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->once())->method('deleteOldLogs')->willReturn(3);
        $model->expects($this->never())->method('runDueJobs');

        $command = new CronRunCommand($model, new CoreParametersHelper());
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('mautic:cron:run'));
        $exitCode = $tester->execute(['--cleanup-only' => true]);

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('3', $tester->getDisplay());
    }

    public function testNormalExecutionWithErrorsReturnsFailure(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->once())->method('runDueJobs')->willReturn(['run' => 1, 'skipped' => 0, 'errors' => 2]);

        $command = new CronRunCommand($model, new CoreParametersHelper());
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('mautic:cron:run'));
        $exitCode = $tester->execute([]);

        $this->assertSame(1, $exitCode, 'Command must return FAILURE when errors > 0');
        $this->assertStringContainsString('1 run', $tester->getDisplay());
        $this->assertStringContainsString('2 errors', $tester->getDisplay());
    }

    public function testNormalExecutionCallsRunDueJobs(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->once())->method('runDueJobs')->willReturn(['run' => 2, 'skipped' => 3, 'errors' => 0]);
        $model->expects($this->never())->method('findDueJobs');

        $command = new CronRunCommand($model, new CoreParametersHelper());
        $app = new Application();
        $app->add($command);

        $tester = new CommandTester($app->find('mautic:cron:run'));
        $exitCode = $tester->execute([]);

        $this->assertSame(0, $exitCode);
        $display = $tester->getDisplay();
        $this->assertStringContainsString('2 run', $display);
        $this->assertStringContainsString('3 skipped', $display);
    }
}
