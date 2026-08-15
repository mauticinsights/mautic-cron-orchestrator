<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Helper;

class CoreParametersHelper
{
    /**
     * @param array<string, mixed> $parameters
     */
    public function __construct(
        private array $parameters = [],
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->parameters;
    }

    public function get($name, $default = null)
    {
        return \array_key_exists($name, $this->parameters) ? $this->parameters[$name] : $default;
    }
}
