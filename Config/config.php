<?php

declare(strict_types=1);

return [
    'name' => 'Mautic Cron Orchestrator',
    'description' => 'Single cron entry point that manages Mautic maintenance jobs: segment rebuild, campaign trigger, broadcast send, webhook processing, and more.',
    'version' => '5.0.0',
    'author' => 'Mautic Insights',
    'routes' => [
        'main' => [
            'mautic_cron_orchestrator_index' => [
                'path' => '/s/cron',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::indexAction',
            ],
            'mautic_cron_orchestrator_edit' => [
                'path' => '/s/cron/edit/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::editAction',
            ],
            'mautic_cron_orchestrator_toggle' => [
                'path' => '/s/cron/toggle/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::toggleAction',
                'method' => 'POST',
            ],
            'mautic_cron_orchestrator_run' => [
                'path' => '/s/cron/run/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::runNowAction',
                'method' => 'POST',
            ],
            'mautic_cron_orchestrator_delete' => [
                'path' => '/s/cron/delete/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::deleteAction',
                'method' => 'POST',
            ],
            'mautic_cron_orchestrator_preset' => [
                'path' => '/s/cron/preset/{preset}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::applyPresetAction',
                'method' => 'POST',
            ],
        ],
        'public' => [],
        'api' => [],
    ],
    'menu' => [
        'main' => [
            'mautic.cron.orchestrator.menu.index' => [
                'route' => 'mautic_cron_orchestrator_index',
                'label' => 'mautic.cron.orchestrator.menu.cron',
                'iconClass' => 'fa fa-clock-o',
                'access' => ['orchestrator:crons:view'],
                'priority' => 50,
            ],
        ],
    ],
    'services' => [
        'events' => [],
        'forms' => [],
        'helpers' => [],
        'other' => [],
        'models' => [
            'mautic.cron.orchestrator.model' => [
                'class' => MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel::class,
            ],
        ],
        'integrations' => [],
        'fixtures' => [],
        'permissions' => [
            'cron.orchestrator.permissions' => [
                'class' => MauticPlugin\MauticCronOrchestratorBundle\Security\Permissions\CronOrchestratorPermissions::class,
                'arguments' => [
                    'mautic.helper.core_parameters',
                ],
            ],
        ],
    ],
    'parameters' => [
        'cron_orchestrator_enabled' => true,
        'cron_orchestrator_default_preset' => 'standard',
        'cron_orchestrator_timeout_minutes' => 60,
        'cron_orchestrator_log_retention_days' => 30,
    ],
];
