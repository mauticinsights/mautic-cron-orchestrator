<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\EventListener;

use Mautic\CoreBundle\CoreEvents;
use Mautic\CoreBundle\Event\MenuEvent;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\Config;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Shows Cron Jobs in the admin menu only when the integration is enabled.
 */
final class MenuSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private Config $config,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            CoreEvents::BUILD_MENU => ['onBuildMenu', 9999],
        ];
    }

    public function onBuildMenu(MenuEvent $event): void
    {
        if ('admin' !== $event->getType() || !$this->config->isPublished()) {
            return;
        }

        $event->addMenuItems([
            'priority' => 50,
            'items' => [
                'mautic.cron.orchestrator.menu.index' => [
                    'id' => 'mautic_cron_orchestrator_index',
                    'route' => 'mautic_cron_orchestrator_index',
                    'label' => 'mautic.cron.orchestrator.menu.cron',
                    'iconClass' => 'ri-time-line',
                    'access' => 'admin',
                    'priority' => 18,
                ],
            ],
        ]);
    }
}
