<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Entity;

class Integration
{
    public function getIsPublished(): bool
    {
        return false;
    }

    public function setIsPublished(bool $published): void
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function getFeatureSettings(): array
    {
        return [];
    }
}
