<?php

declare(strict_types=1);

namespace Mautic\IntegrationsBundle\Integration\Interfaces;

interface ConfigFormInterface
{
    public function getConfigFormName(): ?string;

    public function getConfigFormContentTemplate(): ?string;

    public function getSyncConfigFormName(): ?string;
}
