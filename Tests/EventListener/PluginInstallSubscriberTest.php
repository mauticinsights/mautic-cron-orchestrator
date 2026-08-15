<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\EventListener;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\PluginBundle\Entity\Plugin;
use Mautic\PluginBundle\Event\PluginInstallEvent;
use MauticPlugin\MauticCronOrchestratorBundle\EventListener\PluginInstallSubscriber;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use PHPUnit\Framework\TestCase;

final class PluginInstallSubscriberTest extends TestCase
{
    public function testSubscribesToPluginInstall(): void
    {
        $events = PluginInstallSubscriber::getSubscribedEvents();
        $this->assertArrayHasKey(\Mautic\PluginBundle\PluginEvents::ON_PLUGIN_INSTALL, $events);
    }

    public function testSeedsDefaultPresetForThisBundle(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->once())->method('seedPreset')->with('minimal');

        $plugin = new Plugin();
        $plugin->setBundle('MauticCronOrchestratorBundle');

        $subscriber = new PluginInstallSubscriber($model, new CoreParametersHelper([
            'cron_orchestrator_default_preset' => 'minimal',
        ]));
        $subscriber->onPluginInstall(new PluginInstallEvent($plugin));
    }

    public function testIgnoresOtherPlugins(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->never())->method('seedPreset');

        $plugin = new Plugin();
        $plugin->setBundle('SomeOtherBundle');

        $subscriber = new PluginInstallSubscriber($model, new CoreParametersHelper());
        $subscriber->onPluginInstall(new PluginInstallEvent($plugin));
    }

    public function testFallsBackToStandardForInvalidPreset(): void
    {
        $model = $this->createMock(CronOrchestratorModel::class);
        $model->expects($this->once())->method('seedPreset')->with('standard');

        $plugin = new Plugin();
        $plugin->setBundle('MauticCronOrchestratorBundle');

        $subscriber = new PluginInstallSubscriber($model, new CoreParametersHelper([
            'cron_orchestrator_default_preset' => 'bogus',
        ]));
        $subscriber->onPluginInstall(new PluginInstallEvent($plugin));
    }
}
