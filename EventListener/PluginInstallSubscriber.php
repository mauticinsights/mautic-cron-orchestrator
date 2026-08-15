<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use Mautic\PluginBundle\PluginEvents;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Seeds the default preset when this plugin is first installed.
 */
final class PluginInstallSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private CronOrchestratorModel $orchestrator,
        private CoreParametersHelper $coreParametersHelper,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            PluginEvents::ON_PLUGIN_INSTALL => ['onPluginInstall', 0],
        ];
    }

    public function onPluginInstall(PluginInstallEvent $event): void
    {
        if ('MauticCronOrchestratorBundle' !== $event->getPlugin()->getBundle()) {
            return;
        }

        $preset = (string) $this->coreParametersHelper->get('cron_orchestrator_default_preset', 'standard');
        if (!\in_array($preset, ['minimal', 'standard', 'full'], true)) {
            $preset = 'standard';
        }

        $this->orchestrator->seedPreset($preset);
    }
}
