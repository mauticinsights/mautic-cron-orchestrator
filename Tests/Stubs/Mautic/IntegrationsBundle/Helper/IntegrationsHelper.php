<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Helper;

use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use RuntimeException;

class IntegrationsHelper
{
    public function getIntegration(string $integration): IntegrationInterface
    {
        throw new RuntimeException('Stub');
    }
}
