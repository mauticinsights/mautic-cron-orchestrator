<?php

declare(strict_types=1);

use Mautic\CoreBundle\DependencyInjection\MauticCoreExtension;
use MauticPlugin\MauticCronOrchestratorBundle\Command\CronRunCommand;
use MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\CronOrchestratorIntegration;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\Support\ConfigSupport;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator): void {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure()
        ->public();

    // Repositories are fetched via EntityManager::getRepository() — do not register as services.
    $excludes = ['Repository', 'Assets', 'Resources', 'Translations', 'vendor'];

    $services->load('MauticPlugin\\MauticCronOrchestratorBundle\\', '../')
        ->exclude('../{'.implode(',', array_merge(MauticCoreExtension::DEFAULT_EXCLUDES, $excludes)).'}');

    $services->set(CronOrchestratorModel::class)
        ->arg('$projectDir', '%kernel.project_dir%')
        ->arg('$timeoutMinutes', '%mautic.cron_orchestrator_timeout_minutes%')
        ->arg('$logRetentionDays', '%mautic.cron_orchestrator_log_retention_days%');
    $services->alias('mautic.cron.orchestrator.model', CronOrchestratorModel::class);

    $services->alias('mautic.integration.cronorchestrator', CronOrchestratorIntegration::class);
    $services->alias('cronorchestrator.integration.configuration', ConfigSupport::class);

    $services->set(CronRunCommand::class);
    $services->set(CronController::class);
};
