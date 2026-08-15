<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Security\Permissions;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use Mautic\CoreBundle\Security\Permissions\AbstractPermissions;
use Symfony\Component\Form\FormBuilderInterface;

class CronOrchestratorPermissions extends AbstractPermissions
{
    public function __construct(CoreParametersHelper $coreParametersHelper)
    {
        parent::__construct($coreParametersHelper->all());
    }

    public function getName(): string
    {
        return 'orchestrator';
    }

    public function definePermissions(): void
    {
        $this->addCustomPermission('crons', [
            'view' => 1,
            'edit' => 2,
            'run' => 4,
        ]);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, mixed> $data
     */
    public function buildForm(FormBuilderInterface &$builder, array $options, array $data): void
    {
        $this->addCustomFormFields('orchestrator', 'crons', $builder,
            'mautic.cron.orchestrator.permissions.crons',
            [
                'mautic.cron.orchestrator.permissions.view' => 'view',
                'mautic.cron.orchestrator.permissions.edit' => 'edit',
                'mautic.cron.orchestrator.permissions.run' => 'run',
            ],
            $data
        );
    }
}
