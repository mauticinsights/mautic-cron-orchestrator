<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\MauticCronOrchestratorBundle\Command\CronRunCommand;
use MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    $services->load('MauticPlugin\\MauticCronOrchestratorBundle\\', '../')
        ->exclude('../{'.implode(',', MauticCoreExtension::DEFAULT_EXCLUDES).'}');

    $services->set(CronOrchestratorModel::class);
    $services->set(CronRunCommand::class);
    $services->set(CronController::class);
    $services->set(CronJobRepository::class);
    $services->set(CronJobLogRepository::class);
};
