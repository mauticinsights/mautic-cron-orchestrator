<?php

declare(strict_types=1);

namespace Mautic\CoreBundle\Event;

use Symfony\Contracts\EventDispatcher\Event;

class MenuEvent extends Event
{
    /**
     * @param array<string, mixed> $menuItems
     */
    public function addMenuItems(array $menuItems): void
    {
    }

    public function getType(): string
    {
        return 'main';
    }
}
