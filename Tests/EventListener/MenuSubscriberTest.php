<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use MauticPlugin\MauticCronOrchestratorBundle\EventListener\MenuSubscriber;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\Config;
use PHPUnit\Framework\TestCase;

final class MenuSubscriberTest extends TestCase
{
    public function testSubscribesToBuildMenu(): void
    {
        $this->assertArrayHasKey(CoreEvents::BUILD_MENU, MenuSubscriber::getSubscribedEvents());
    }

    public function testDoesNotAddMenuWhenDisabled(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isPublished')->willReturn(false);

        $event = $this->createMock(MenuEvent::class);
        $event->method('getType')->willReturn('admin');
        $event->expects($this->never())->method('addMenuItems');

        (new MenuSubscriber($config))->onBuildMenu($event);
    }

    public function testAddsMenuWhenPublishedOnAdminMenu(): void
    {
        $config = $this->createMock(Config::class);
        $config->method('isPublished')->willReturn(true);

        $event = $this->createMock(MenuEvent::class);
        $event->method('getType')->willReturn('admin');
        $event->expects($this->once())->method('addMenuItems');

        (new MenuSubscriber($config))->onBuildMenu($event);
    }

    public function testIgnoresNonAdminMenu(): void
    {
        $config = $this->createMock(Config::class);
        $config->expects($this->never())->method('isPublished');

        $event = $this->createMock(MenuEvent::class);
        $event->method('getType')->willReturn('main');
        $event->expects($this->never())->method('addMenuItems');

        (new MenuSubscriber($config))->onBuildMenu($event);
    }
}
