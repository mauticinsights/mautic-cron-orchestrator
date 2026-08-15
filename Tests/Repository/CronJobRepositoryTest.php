<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Repository;

use DateTimeImmutable;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use PHPUnit\Framework\TestCase;

/**
 * Validates repository query logic at the entity level.
 *
 * Full integration tests (against a real Doctrine-managed DB) are not possible
 * in standalone plugin dev without the full Mautic stack. These tests verify
 * the entity state predicates that the repository queries rely on.
 */
final class CronJobRepositoryTest extends TestCase
{
    // ──────────────────────────────────────────────────────────────
    // findEnabled — entity state predicate
    // ──────────────────────────────────────────────────────────────

    public function testEnabledJobHasIsEnabledTrue(): void
    {
        $job = new CronJob();
        $this->assertTrue($job->isEnabled(), 'New job defaults to enabled');

        $job->setIsEnabled(true);
        $this->assertTrue($job->isEnabled());
    }

    public function testDisabledJobHasIsEnabledFalse(): void
    {
        $job = new CronJob();
        $job->setIsEnabled(false);
        $this->assertFalse($job->isEnabled());
    }

    // ──────────────────────────────────────────────────────────────
    // findStuck — entity state predicate
    // ──────────────────────────────────────────────────────────────

    public function testStuckJobIsRunningOlderThanTimeout(): void
    {
        $now = new DateTimeImmutable('2026-07-18 12:00:00');
        $timeoutMinutes = 60;

        $stuck = new CronJob();
        $stuck->setLastRunAt($now->modify('-61 minutes'));
        $stuck->setLastRunStatus('running');

        $isRunning = 'running' === $stuck->getLastRunStatus();
        $isOlderThanTimeout = null !== $stuck->getLastRunAt()
            && $stuck->getLastRunAt() < $now->modify("-{$timeoutMinutes} minutes");

        $this->assertTrue($isRunning && $isOlderThanTimeout, 'Job running for >60min must be stuck');
    }

    public function testFreshRunningJobNotStuck(): void
    {
        $now = new DateTimeImmutable('2026-07-18 12:00:00');
        $timeoutMinutes = 60;

        $fresh = new CronJob();
        $fresh->setLastRunAt($now->modify('-5 minutes'));
        $fresh->setLastRunStatus('running');

        $isStuck = 'running' === $fresh->getLastRunStatus()
            && null !== $fresh->getLastRunAt()
            && $fresh->getLastRunAt() < $now->modify("-{$timeoutMinutes} minutes");

        $this->assertFalse($isStuck, 'Job running for 5min must not be stuck');
    }

    public function testCompletedJobNotStuckEvenIfOld(): void
    {
        $now = new DateTimeImmutable('2026-07-18 12:00:00');

        $oldSuccess = new CronJob();
        $oldSuccess->setLastRunAt($now->modify('-120 minutes'));
        $oldSuccess->setLastRunStatus('success');

        $isStuck = 'running' === $oldSuccess->getLastRunStatus();
        $this->assertFalse($isStuck, 'Completed job must not be stuck regardless of age');
    }

    // ──────────────────────────────────────────────────────────────
    // deleteByPreset — entity state predicate
    // ──────────────────────────────────────────────────────────────

    public function testPresetMatchesFilterValue(): void
    {
        $custom = new CronJob();
        $custom->setPreset('custom');
        $minimal = new CronJob();
        $minimal->setPreset('minimal');

        $this->assertSame('custom', $custom->getPreset());
        $this->assertSame('minimal', $minimal->getPreset());

        // The QB WHERE predicate in deleteByPreset is: j.preset = :preset
        $this->assertTrue('minimal' === $minimal->getPreset(), 'Entity preset value must match the filter');
        $this->assertFalse('minimal' === $custom->getPreset(), 'Entity with different preset must not match');
    }

    // ──────────────────────────────────────────────────────────────
    // CronJobLog relationship
    // ──────────────────────────────────────────────────────────────

    public function testCronJobLogReferencesJob(): void
    {
        $job = new CronJob();
        $log = new \MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog();
        $log->setCronJob($job);

        $this->assertSame($job, $log->getCronJob());
    }
}
