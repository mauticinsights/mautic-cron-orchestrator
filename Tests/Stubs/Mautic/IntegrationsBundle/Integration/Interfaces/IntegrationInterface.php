<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

use Mautic\PluginBundle\Entity\Integration;

interface IntegrationInterface
{
    public function getIntegrationConfiguration(): Integration;
}
