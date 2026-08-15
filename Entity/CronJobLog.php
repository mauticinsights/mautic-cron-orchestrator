<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository::class)]
#[ORM\Table(name: 'cron_job_log')]
class CronJobLog
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\ManyToOne(targetEntity: CronJob::class)]
    #[ORM\JoinColumn(name: 'cron_job_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private CronJob $cronJob;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $startedAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $finishedAt = null;

    #[ORM\Column(type: 'string', length: 20)]
    private string $status = 'running';

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $durationSeconds = null;

    #[ORM\Column(type: 'integer', nullable: true)]
    private ?int $exitCode = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $output = null;

    public function __construct()
    {
        $this->startedAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getCronJob(): CronJob
    {
        return $this->cronJob;
    }

    public function setCronJob(CronJob $cronJob): void
    {
        $this->cronJob = $cronJob;
    }

    public function getStartedAt(): DateTimeInterface
    {
        return $this->startedAt;
    }

    public function setStartedAt(DateTimeInterface $startedAt): void
    {
        $this->startedAt = $startedAt;
    }

    public function getFinishedAt(): ?DateTimeInterface
    {
        return $this->finishedAt;
    }

    public function setFinishedAt(?DateTimeInterface $finishedAt): void
    {
        $this->finishedAt = $finishedAt;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): void
    {
        $this->status = $status;
    }

    public function getDurationSeconds(): ?float
    {
        return $this->durationSeconds;
    }

    public function setDurationSeconds(?float $durationSeconds): void
    {
        $this->durationSeconds = $durationSeconds;
    }

    public function getExitCode(): ?int
    {
        return $this->exitCode;
    }

    public function setExitCode(?int $exitCode): void
    {
        $this->exitCode = $exitCode;
    }

    public function getOutput(): ?string
    {
        return $this->output;
    }

    public function setOutput(?string $output): void
    {
        $this->output = $output;
    }
}
