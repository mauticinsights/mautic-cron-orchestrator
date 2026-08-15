<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\PluginBundle\Entity\Integration;

/**
 * Runtime gate for the Plugins UI Enabled toggle ({@see Integration::$isPublished}).
 */
class Config
{
    public function __construct(
        private IntegrationsHelper $integrationsHelper,
    ) {
    }

    public function isPublished(): bool
    {
        try {
            return (bool) $this->getIntegrationEntity()->getIsPublished();
        } catch (IntegrationNotFoundException) {
            return false;
        }
    }

    /**
     * @throws IntegrationNotFoundException
     */
    public function getIntegrationEntity(): Integration
    {
        $integrationObject = $this->integrationsHelper->getIntegration(CronOrchestratorIntegration::NAME);

        return $integrationObject->getIntegrationConfiguration();
    }
}
