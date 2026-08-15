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
                'path' => '/cron',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::indexAction',
            ],
            'mautic_cron_orchestrator_edit' => [
                'path' => '/cron/edit/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::editAction',
            ],
            'mautic_cron_orchestrator_toggle' => [
                'path' => '/cron/toggle/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::toggleAction',
                'method' => 'POST',
            ],
            'mautic_cron_orchestrator_run' => [
                'path' => '/cron/run/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::runNowAction',
                'method' => 'POST',
            ],
            'mautic_cron_orchestrator_delete' => [
                'path' => '/cron/delete/{objectId}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::deleteAction',
                'method' => 'POST',
            ],
            'mautic_cron_orchestrator_preset' => [
                'path' => '/cron/preset/{preset}',
                'controller' => 'MauticPlugin\MauticCronOrchestratorBundle\Controller\CronController::applyPresetAction',
                'method' => 'POST',
            ],
        ],
        'public' => [],
        'api' => [],
    ],
    'menu' => [
        // Admin menu item is registered by MenuSubscriber when the integration is published.
    ],
    'services' => [
        'events' => [],
        'forms' => [],
        'helpers' => [],
        'other' => [],
        'models' => [],
        'integrations' => [],
        'fixtures' => [],
        'permissions' => [],
    ],
    'parameters' => [
        'cron_orchestrator_default_preset' => 'standard',
        'cron_orchestrator_timeout_minutes' => 60,
        'cron_orchestrator_log_retention_days' => 30,
    ],
];
