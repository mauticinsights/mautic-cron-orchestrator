<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Tests\DependencyInjection;

use MauticPlugin\MauticCronOrchestratorBundle\DependencyInjection\MauticCronOrchestratorExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class MauticCronOrchestratorExtensionTest extends TestCase
{
    public function testPrependRegistersTwigPathWhenViewsExist(): void
    {
        $container = new ContainerBuilder();
        $extension = new MauticCronOrchestratorExtension();
        $extension->prepend($container);

        $twig = $container->getExtensionConfig('twig');
        $this->assertNotEmpty($twig);
        $this->assertArrayHasKey('paths', $twig[0]);
        $paths = $twig[0]['paths'];
        $this->assertContains('MauticCronOrchestratorBundle', $paths);
        $this->assertTrue(is_dir(array_key_first($paths) ?: ''));
    }
}
