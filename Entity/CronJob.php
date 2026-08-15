<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Entity;

use DateTimeImmutable;
use DateTimeInterface;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository::class)]
#[ORM\Table(name: 'cron_job')]
class CronJob
{
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ORM\Column(type: 'integer')]
    private int $id = 0;

    #[ORM\Column(type: 'string', length: 100)]
    private string $name;

    #[ORM\Column(type: 'string', length: 255)]
    private string $command;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $arguments = null;

    #[ORM\Column(type: 'integer')]
    private int $frequencyMinutes;

    #[ORM\Column(type: 'boolean')]
    private bool $isEnabled = true;

    #[ORM\Column(type: 'string', length: 20)]
    private string $preset = 'custom';

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $lastRunAt = null;

    #[ORM\Column(type: 'string', length: 20, nullable: true)]
    private ?string $lastRunStatus = null;

    #[ORM\Column(type: 'float', nullable: true)]
    private ?float $lastDurationSeconds = null;

    #[ORM\Column(type: 'datetime')]
    private DateTimeInterface $createdAt;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?DateTimeInterface $modifiedAt = null;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getCommand(): string
    {
        return $this->command;
    }

    public function setCommand(string $command): void
    {
        $this->command = $command;
    }

    public function getArguments(): ?string
    {
        return $this->arguments;
    }

    public function setArguments(?string $arguments): void
    {
        $this->arguments = $arguments;
    }

    public function getFrequencyMinutes(): int
    {
        return $this->frequencyMinutes;
    }

    public function setFrequencyMinutes(int $frequencyMinutes): void
    {
        $this->frequencyMinutes = $frequencyMinutes;
    }

    public function isEnabled(): bool
    {
        return $this->isEnabled;
    }

    public function setIsEnabled(bool $isEnabled): void
    {
        $this->isEnabled = $isEnabled;
    }

    public function getPreset(): string
    {
        return $this->preset;
    }

    public function setPreset(string $preset): void
    {
        $this->preset = $preset;
    }

    public function getLastRunAt(): ?DateTimeInterface
    {
        return $this->lastRunAt;
    }

    public function setLastRunAt(?DateTimeInterface $lastRunAt): void
    {
        $this->lastRunAt = $lastRunAt;
    }

    public function getLastRunStatus(): ?string
    {
        return $this->lastRunStatus;
    }

    public function setLastRunStatus(?string $lastRunStatus): void
    {
        $this->lastRunStatus = $lastRunStatus;
    }

    public function getLastDurationSeconds(): ?float
    {
        return $this->lastDurationSeconds;
    }

    public function setLastDurationSeconds(?float $lastDurationSeconds): void
    {
        $this->lastDurationSeconds = $lastDurationSeconds;
    }

    public function getCreatedAt(): DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getModifiedAt(): ?DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(?DateTimeInterface $modifiedAt): void
    {
        $this->modifiedAt = $modifiedAt;
    }
}
