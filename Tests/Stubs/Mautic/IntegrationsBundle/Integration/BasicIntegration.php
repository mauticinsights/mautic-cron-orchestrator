<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration;

use Mautic\PluginBundle\Entity\Integration;
use RuntimeException;

class BasicIntegration
{
    public function setIntegrationSettings(Integration $integration): void
    {
    }

    public function getIntegrationConfiguration(): Integration
    {
        throw new RuntimeException('Stub');
    }
}
