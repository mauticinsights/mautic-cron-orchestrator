<?php

declare(strict_types=1);

namespace Mautic\PluginBundle\Entity;

class Plugin
{
    private string $bundle = '';
    private string $name = '';

    public function getBundle(): string
    {
        return $this->bundle;
    }

    public function setBundle(string $bundle): void
    {
        $this->bundle = $bundle;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
