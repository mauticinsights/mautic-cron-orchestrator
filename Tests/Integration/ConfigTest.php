<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\Integration;

use Mautic\IntegrationsBundle\Exception\IntegrationNotFoundException;
use Mautic\IntegrationsBundle\Helper\IntegrationsHelper;
use Mautic\IntegrationsBundle\Integration\Interfaces\IntegrationInterface;
use Mautic\PluginBundle\Entity\Integration;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\Config;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\CronOrchestratorIntegration;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testIsPublishedTrueWhenIntegrationEnabled(): void
    {
        $entity = $this->createMock(Integration::class);
        $entity->method('getIsPublished')->willReturn(true);

        $integration = $this->createMock(IntegrationInterface::class);
        $integration->method('getIntegrationConfiguration')->willReturn($entity);

        $helper = $this->createMock(IntegrationsHelper::class);
        $helper->method('getIntegration')
            ->with(CronOrchestratorIntegration::NAME)
            ->willReturn($integration);

        $this->assertTrue((new Config($helper))->isPublished());
    }

    public function testIsPublishedFalseWhenIntegrationDisabled(): void
    {
        $entity = $this->createMock(Integration::class);
        $entity->method('getIsPublished')->willReturn(false);

        $integration = $this->createMock(IntegrationInterface::class);
        $integration->method('getIntegrationConfiguration')->willReturn($entity);

        $helper = $this->createMock(IntegrationsHelper::class);
        $helper->method('getIntegration')->willReturn($integration);

        $this->assertFalse((new Config($helper))->isPublished());
    }

    public function testIsPublishedFalseWhenIntegrationMissing(): void
    {
        $helper = $this->createMock(IntegrationsHelper::class);
        $helper->method('getIntegration')
            ->willThrowException(new IntegrationNotFoundException('missing'));

        $this->assertFalse((new Config($helper))->isPublished());
    }
}
