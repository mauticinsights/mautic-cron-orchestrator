<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Integration\Support;

use Mautic\IntegrationsBundle\Integration\DefaultConfigFormTrait;
use Mautic\IntegrationsBundle\Integration\Interfaces\ConfigFormInterface;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\CronOrchestratorIntegration;

class ConfigSupport extends CronOrchestratorIntegration implements ConfigFormInterface
{
    use DefaultConfigFormTrait;
}
