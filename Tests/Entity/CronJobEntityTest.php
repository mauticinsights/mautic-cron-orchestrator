<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Entity;

use DateTimeImmutable;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use PHPUnit\Framework\TestCase;

final class CronJobEntityTest extends TestCase
{
    public function testDefaultsAndAccessors(): void
    {
        $job = new CronJob();
        $job->setName('Segments');
        $job->setCommand('mautic:segments:update');
        $job->setArguments('--no-ansi');
        $job->setFrequencyMinutes(15);
        $job->setIsEnabled(false);
        $job->setPreset('standard');
        $lastRun = new DateTimeImmutable('2026-08-15 12:00:00');
        $job->setLastRunAt($lastRun);
        $job->setLastRunStatus('success');
        $modified = new DateTimeImmutable('2026-08-15 13:00:00');
        $job->setModifiedAt($modified);

        $this->assertSame(0, $job->getId());
        $this->assertSame('Segments', $job->getName());
        $this->assertSame('mautic:segments:update', $job->getCommand());
        $this->assertSame('--no-ansi', $job->getArguments());
        $this->assertSame(15, $job->getFrequencyMinutes());
        $this->assertFalse($job->isEnabled());
        $this->assertSame('standard', $job->getPreset());
        $this->assertSame($lastRun, $job->getLastRunAt());
        $this->assertSame('success', $job->getLastRunStatus());
        $this->assertInstanceOf(DateTimeImmutable::class, $job->getCreatedAt());
        $this->assertSame($modified, $job->getModifiedAt());
        $job->setLastDurationSeconds(12.5);
        $this->assertSame(12.5, $job->getLastDurationSeconds());
    }

    public function testDefaultPresetIsCustomAndEnabled(): void
    {
        $job = new CronJob();
        $this->assertSame('custom', $job->getPreset());
        $this->assertTrue($job->isEnabled());
        $this->assertNull($job->getArguments());
        $this->assertNull($job->getLastRunAt());
        $this->assertNull($job->getLastRunStatus());
        $this->assertNull($job->getLastDurationSeconds());
        $this->assertNull($job->getModifiedAt());
    }
}
