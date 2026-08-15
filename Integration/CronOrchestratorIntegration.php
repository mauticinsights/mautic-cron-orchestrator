<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Integration;

use Mautic\IntegrationsBundle\Integration\BasicIntegration;
use Mautic\IntegrationsBundle\Integration\ConfigurationTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\BasicInterface;

class CronOrchestratorIntegration extends BasicIntegration implements BasicInterface
{
    use ConfigurationTrait;

    public const NAME = 'CronOrchestrator';

    public const DISPLAY_NAME = 'Cron Orchestrator';

    public function getName(): string
    {
        return self::NAME;
    }

    public function getDisplayName(): string
    {
        return self::DISPLAY_NAME;
    }

    public function getIcon(): string
    {
        return 'plugins/MauticCronOrchestratorBundle/Assets/img/logo.svg';
    }
}
