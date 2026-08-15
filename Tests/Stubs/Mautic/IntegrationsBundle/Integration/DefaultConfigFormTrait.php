<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration;

trait DefaultConfigFormTrait
{
    public function getConfigFormName(): ?string
    {
        return null;
    }

    public function getConfigFormContentTemplate(): ?string
    {
        return null;
    }

    public function getSyncConfigFormName(): ?string
    {
        return null;
    }
}
