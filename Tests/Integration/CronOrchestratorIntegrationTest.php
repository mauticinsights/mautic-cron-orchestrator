<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Integration;

use MauticPlugin\MauticCronOrchestratorBundle\Integration\CronOrchestratorIntegration;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\Support\ConfigSupport;
use PHPUnit\Framework\TestCase;

final class CronOrchestratorIntegrationTest extends TestCase
{
    public function testIdentityAndIcon(): void
    {
        $integration = new CronOrchestratorIntegration();

        $this->assertSame('CronOrchestrator', CronOrchestratorIntegration::NAME);
        $this->assertSame('Cron Orchestrator', CronOrchestratorIntegration::DISPLAY_NAME);
        $this->assertSame(CronOrchestratorIntegration::NAME, $integration->getName());
        $this->assertSame(CronOrchestratorIntegration::DISPLAY_NAME, $integration->getDisplayName());
        $this->assertSame(
            'plugins/MauticCronOrchestratorBundle/Assets/img/logo.svg',
            $integration->getIcon()
        );
    }

    public function testConfigSupportUsesDefaultFormTrait(): void
    {
        $support = new ConfigSupport();

        $this->assertSame(CronOrchestratorIntegration::NAME, $support->getName());
        $this->assertNull($support->getConfigFormName());
        $this->assertNull($support->getConfigFormContentTemplate());
        $this->assertNull($support->getSyncConfigFormName());
    }
}
