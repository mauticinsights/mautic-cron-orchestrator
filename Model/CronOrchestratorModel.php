<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Model;

use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use Throwable;

class CronOrchestratorModel
{
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private string $projectDir,
        private int $timeoutMinutes = 60,
        private int $logRetentionDays = 30,
    ) {
    }

    public function setTimeoutMinutes(int $timeoutMinutes): void
    {
        $this->timeoutMinutes = $timeoutMinutes;
    }

    public function setLogRetentionDays(int $logRetentionDays): void
    {
        $this->logRetentionDays = $logRetentionDays;
    }

    /**
     * @return array{run: int, skipped: int, errors: int}
     */
    public function runDueJobs(): array
    {
        $repo = $this->getCronJobRepository();
        $jobs = $repo->findEnabled();
        $stats = ['run' => 0, 'skipped' => 0, 'errors' => 0];

        // Reset stuck jobs before processing
        $stuck = $repo->findStuck($this->timeoutMinutes);
        foreach ($stuck as $stuckJob) {
            $this->logger->warning('Cron orchestrator: resetting stuck job {job}', ['job' => $stuckJob->getName()]);
            $stuckJob->setLastRunStatus('timeout');
            $repo->save($stuckJob);
        }

        $now = new DateTimeImmutable();

        foreach ($jobs as $job) {
            if (!$this->isDue($job, $now)) {
                ++$stats['skipped'];
                continue;
            }

            $this->em->clear();
            // Re-fetch to get fresh state (avoid stale running flag from a previous iteration)
            $fresh = $repo->find($job->getId());
            if (null === $fresh) {
                continue;
            }
            if ('running' === $fresh->getLastRunStatus()) {
                ++$stats['skipped'];
                continue;
            }

            $log = $this->startJob($fresh);
            try {
                $this->runCommand($fresh, $log);
                if (\in_array($log->getStatus(), ['error', 'timeout'], true)) {
                    ++$stats['errors'];
                } else {
                    ++$stats['run'];
                }
            } catch (Throwable $e) {
                $this->logger->error('Cron orchestrator: job {job} failed: {error}', [
                    'job' => $fresh->getName(),
                    'error' => $e->getMessage(),
                ]);
                $log->setStatus('error');
                $log->setOutput(($log->getOutput() ?? '')."\n".$e->getMessage());
                $fresh->setLastRunStatus('error');
                ++$stats['errors'];
            }

            $log->setFinishedAt(new DateTimeImmutable());
            $this->applyDuration($fresh, $log);
            $this->getCronJobLogRepository()->save($log);
            $repo->save($fresh);
        }

        $this->cleanupOldLogs();

        return $stats;
    }

    /**
     * Run a single job immediately (manual trigger).
     *
     * @return array{status: string, output: string}
     */
    public function runJobNow(CronJob $job): array
    {
        $repo = $this->getCronJobRepository();
        $logRepo = $this->getCronJobLogRepository();

        $log = $this->startJob($job);
        try {
            $this->runCommand($job, $log);
            $status = \in_array($log->getStatus(), ['error', 'timeout'], true) ? $log->getStatus() : 'success';
        } catch (Throwable $e) {
            $log->setStatus('error');
            $log->setOutput(($log->getOutput() ?? '')."\n".$e->getMessage());
            $job->setLastRunStatus('error');
            $status = 'error';
        }

        $log->setFinishedAt(new DateTimeImmutable());
        $this->applyDuration($job, $log);
        $logRepo->save($log);
        $repo->save($job);

        return ['status' => $status, 'output' => (string) $log->getOutput()];
    }

    /**
     * Seed preset jobs. Replaces all jobs from any built-in preset (keeps custom jobs).
     *
     * @return list<CronJob>
     */
    public function seedPreset(string $preset): array
    {
        $repo = $this->getCronJobRepository();
        $repo->deleteManagedPresets();

        $jobs = self::getPresetJobs($preset);
        $created = [];
        foreach ($jobs as $def) {
            $job = new CronJob();
            $job->setName($def['name']);
            $job->setCommand($def['command']);
            $job->setArguments($def['arguments'] ?? null);
            $job->setFrequencyMinutes($def['frequency']);
            $job->setIsEnabled(true);
            $job->setPreset($preset);
            $this->em->persist($job);
            $created[] = $job;
        }
        $this->em->flush();

        return $created;
    }

    public function deleteOldLogs(): int
    {
        $threshold = new DateTimeImmutable("-{$this->logRetentionDays} days");

        return $this->getCronJobLogRepository()->deleteOlderThan($threshold);
    }

    public function isDue(CronJob $job, DateTimeImmutable $now): bool
    {
        $lastRun = $job->getLastRunAt();
        if (null === $lastRun) {
            return true;
        }

        $nextRun = DateTimeImmutable::createFromInterface($lastRun)
            ->modify("+{$job->getFrequencyMinutes()} minutes");

        return $nextRun <= $now;
    }

    /**
     * @return list<CronJob>
     */
    public function findDueJobs(): array
    {
        $now = new DateTimeImmutable();
        $due = [];
        foreach ($this->getCronJobRepository()->findEnabled() as $job) {
            if ($this->isDue($job, $now)) {
                $due[] = $job;
            }
        }

        return $due;
    }

    /**
     * Presets aligned with https://docs.mautic.org/en/5.2/configuration/cron_jobs.html.
     *
     * Frequencies for the core segment/campaign jobs are slightly staggered so they
     * are less likely to become due in the same orchestrator minute.
     *
     * @return array<int, array{name: string, command: string, arguments?: string, frequency: int}>
     */
    public static function getPresetJobs(string $preset): array
    {
        $args = '--no-interaction --no-ansi';

        // Required jobs from the official docs (campaigns:update === campaigns:rebuild).
        $minimal = [
            ['name' => 'Segment updates', 'command' => 'mautic:segments:update', 'arguments' => $args, 'frequency' => 15],
            ['name' => 'Campaign update', 'command' => 'mautic:campaigns:update', 'arguments' => $args, 'frequency' => 20],
            ['name' => 'Campaign trigger', 'command' => 'mautic:campaigns:trigger', 'arguments' => $args, 'frequency' => 25],
            ['name' => 'Message send', 'command' => 'mautic:messages:send', 'arguments' => $args, 'frequency' => 15],
            ['name' => 'Custom field columns', 'command' => 'mautic:custom-field:create-column', 'arguments' => $args, 'frequency' => 15],
        ];

        // Common optional jobs from the docs.
        $standard = array_merge($minimal, [
            ['name' => 'Broadcast emails', 'command' => 'mautic:broadcasts:send', 'arguments' => $args, 'frequency' => 15],
            ['name' => 'Webhook processing', 'command' => 'mautic:webhooks:process', 'arguments' => $args, 'frequency' => 15],
            ['name' => 'Contact import', 'command' => 'mautic:import', 'arguments' => $args, 'frequency' => 15],
            ['name' => 'Inbound email (bounces)', 'command' => 'mautic:email:fetch', 'arguments' => $args, 'frequency' => 30],
            ['name' => 'Scheduled reports', 'command' => 'mautic:reports:scheduler', 'arguments' => $args, 'frequency' => 60],
        ]);

        // Maintenance / compliance / niche optionals.
        $full = array_merge($standard, [
            ['name' => 'GeoIP update', 'command' => 'mautic:iplookup:download', 'arguments' => $args, 'frequency' => 10080],
            ['name' => 'Maintenance cleanup', 'command' => 'mautic:maintenance:cleanup', 'arguments' => $args.' --days-old=365', 'frequency' => 1440],
            ['name' => 'MaxMind do-not-sell download', 'command' => 'mautic:donotsell:download', 'arguments' => $args, 'frequency' => 10080],
            ['name' => 'MaxMind purge', 'command' => 'mautic:max-mind:purge', 'arguments' => $args, 'frequency' => 10080],
            ['name' => 'Unused IP cleanup', 'command' => 'mautic:unusedip:delete', 'arguments' => $args, 'frequency' => 10080],
            ['name' => 'Social monitoring', 'command' => 'mautic:social:monitoring', 'arguments' => $args, 'frequency' => 60],
            ['name' => 'Scheduled contact export', 'command' => 'mautic:contacts:scheduled_export', 'arguments' => $args, 'frequency' => 60],
        ]);

        return match ($preset) {
            'minimal' => $minimal,
            'full' => $full,
            default => $standard,
        };
    }

    private function startJob(CronJob $job): CronJobLog
    {
        $log = new CronJobLog();
        $log->setCronJob($job);
        $log->setStartedAt(new DateTimeImmutable());
        $log->setStatus('running');

        $job->setLastRunAt(new DateTimeImmutable());
        $job->setLastRunStatus('running');

        $this->getCronJobRepository()->save($job);

        return $log;
    }

    private function runCommand(CronJob $job, CronJobLog $log): void
    {
        $fullCommand = 'php bin/console '.$job->getCommand();
        if (null !== ($args = $job->getArguments()) && '' !== trim($args)) {
            $fullCommand .= ' '.$args;
        }

        $process = Process::fromShellCommandline($fullCommand, $this->projectDir);
        $process->setTimeout(max(1, $this->timeoutMinutes) * 60);
        $started = microtime(true);

        try {
            $process->run();
        } catch (ProcessTimedOutException $e) {
            $log->setExitCode($process->getExitCode());
            $log->setOutput(
                $process->getOutput()
                .$process->getErrorOutput()
                ."\nTimed out after {$this->timeoutMinutes} minute(s)."
            );
            $log->setStatus('timeout');
            $log->setDurationSeconds(microtime(true) - $started);
            $job->setLastRunStatus('timeout');
            $this->logger->warning('Cron orchestrator: job {job} timed out after {minutes} minute(s)', [
                'job' => $job->getName(),
                'minutes' => $this->timeoutMinutes,
            ]);

            return;
        }

        $log->setExitCode($process->getExitCode());
        $log->setOutput($process->getOutput().$process->getErrorOutput());
        $log->setDurationSeconds(microtime(true) - $started);

        if ($process->isSuccessful()) {
            $log->setStatus('success');
            $job->setLastRunStatus('success');
        } else {
            $log->setStatus('error');
            $job->setLastRunStatus('error');
        }
    }

    private function applyDuration(CronJob $job, CronJobLog $log): void
    {
        if (null === $log->getDurationSeconds() && null !== $log->getStartedAt()) {
            $log->setDurationSeconds((float) (microtime(true) - (float) $log->getStartedAt()->format('U.u')));
        }

        if (null !== $log->getDurationSeconds()) {
            $job->setLastDurationSeconds($log->getDurationSeconds());
        }
    }

    private function cleanupOldLogs(): void
    {
        $deleted = $this->deleteOldLogs();
        if ($deleted > 0) {
            $this->logger->info('Cron orchestrator: cleaned up {count} old log rows', ['count' => $deleted]);
        }
    }

    private function getCronJobRepository(): CronJobRepository
    {
        $repo = $this->em->getRepository(CronJob::class);
        \assert($repo instanceof CronJobRepository);

        return $repo;
    }

    private function getCronJobLogRepository(): CronJobLogRepository
    {
        $repo = $this->em->getRepository(CronJobLog::class);
        \assert($repo instanceof CronJobLogRepository);

        return $repo;
    }
}
