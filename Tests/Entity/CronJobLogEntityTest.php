<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Entity;

use DateTimeImmutable;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog;
use PHPUnit\Framework\TestCase;

final class CronJobLogEntityTest extends TestCase
{
    public function testDefaultsAndAccessors(): void
    {
        $job = new CronJob();
        $job->setName('job');
        $job->setCommand('mautic:test');
        $job->setFrequencyMinutes(15);

        $log = new CronJobLog();
        $started = new DateTimeImmutable('2026-08-15 12:00:00');
        $finished = new DateTimeImmutable('2026-08-15 12:01:00');
        $log->setCronJob($job);
        $log->setStartedAt($started);
        $log->setFinishedAt($finished);
        $log->setStatus('success');
        $log->setDurationSeconds(12.5);
        $log->setExitCode(0);
        $log->setOutput('ok');

        $this->assertSame(0, $log->getId());
        $this->assertSame($job, $log->getCronJob());
        $this->assertSame($started, $log->getStartedAt());
        $this->assertSame($finished, $log->getFinishedAt());
        $this->assertSame('success', $log->getStatus());
        $this->assertSame(12.5, $log->getDurationSeconds());
        $this->assertSame(0, $log->getExitCode());
        $this->assertSame('ok', $log->getOutput());
    }

    public function testConstructorSetsRunningDefaults(): void
    {
        $log = new CronJobLog();
        $this->assertSame('running', $log->getStatus());
        $this->assertInstanceOf(DateTimeImmutable::class, $log->getStartedAt());
        $this->assertNull($log->getFinishedAt());
        $this->assertNull($log->getDurationSeconds());
        $this->assertNull($log->getExitCode());
        $this->assertNull($log->getOutput());
    }
}
