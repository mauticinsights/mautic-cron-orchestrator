<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Controller;

use DateTimeImmutable;
use Mautic\CoreBundle\Controller\FormController;
use Mautic\CoreBundle\Form\Type\FormButtonsType;
use Mautic\CoreBundle\Form\Type\YesNoButtonGroupType;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\Config;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Constraints\GreaterThan;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Mautic admin UI for managing cron jobs.
 *
 * Unit tests not feasible in standalone mode — depends on Mautic FormController
 * ($this->security, $this->getDoctrine(), $this->delegateView(), $this->createFormBuilder()).
 * Tested via manual verification in a running Mautic instance.
 */
class CronController extends FormController
{
    public function indexAction(Request $request, Config $config): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        $session = $request->getSession();
        $search  = (string) $request->get('search', $session->get('mautic.cron_orchestrator.filter', ''));
        $session->set('mautic.cron_orchestrator.filter', $search);

        $repo = $this->cronJobRepository();
        $jobs = $repo->search($search);

        return $this->delegateView([
            'viewParameters' => [
                'jobs'          => $jobs,
                'searchValue'   => $search,
                'tmpl'          => $request->get('tmpl', 'index'),
                'activePreset'  => $repo->findActivePreset(),
                'presets'       => [
                    'minimal'  => CronOrchestratorModel::getPresetJobs('minimal'),
                    'standard' => CronOrchestratorModel::getPresetJobs('standard'),
                    'full'     => CronOrchestratorModel::getPresetJobs('full'),
                    'custom'   => CronOrchestratorModel::getPresetJobs('custom'),
                ],
            ],
            'contentTemplate' => '@MauticCronOrchestratorBundle/Cron/index.html.twig',
            'passthroughVars' => [
                'activeLink' => '#mautic_cron_orchestrator_index',
                'route'      => $this->generateUrl('mautic_cron_orchestrator_index'),
            ],
        ]);
    }

    public function lastLogAction(Config $config, int|string $objectId): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        $job = $this->cronJobRepository()->find((int) $objectId);
        if (null === $job) {
            return $this->notFound();
        }

        $logs = $this->cronJobLogRepository()->findRecentByJob($job, 1);

        return $this->delegateView([
            'viewParameters' => [
                'job' => $job,
                'log' => $logs[0] ?? null,
            ],
            'contentTemplate' => '@MauticCronOrchestratorBundle/Cron/last_log.html.twig',
            'passthroughVars' => [
                'route' => false,
            ],
        ]);
    }

    public function editAction(Request $request, Config $config, int|string $objectId = 0): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        $repo = $this->cronJobRepository();
        $logRepo = $this->cronJobLogRepository();

        $job = 0 !== (int) $objectId ? $repo->find((int) $objectId) : null;
        $isNew = null === $job;

        if (!$isNew) {
            $recentLogs = $logRepo->findRecentByJob($job, 10);
        } else {
            $recentLogs = [];
            $job = new CronJob();
            $job->setPreset('custom');
            $job->setName('');
            $job->setCommand('');
            $job->setFrequencyMinutes(15);
        }

        $action = $this->generateUrl('mautic_cron_orchestrator_edit', ['objectId' => $objectId]);

        $form = $this->createFormBuilder($job, ['action' => $action])
            ->add('name', TextType::class, [
                'label'       => 'mautic.cron.orchestrator.form.name',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => true,
                'empty_data'  => '',
                'constraints' => [
                    new NotBlank(['message' => 'mautic.core.value.required']),
                ],
            ])
            ->add('command', TextType::class, [
                'label'       => 'mautic.cron.orchestrator.form.command',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => true,
                'empty_data'  => '',
                'constraints' => [
                    new NotBlank(['message' => 'mautic.core.value.required']),
                ],
            ])
            ->add('arguments', TextType::class, [
                'label'      => 'mautic.cron.orchestrator.form.arguments',
                'label_attr' => ['class' => 'control-label'],
                'attr'       => ['class' => 'form-control'],
                'required'   => false,
                'empty_data' => null,
            ])
            ->add('frequencyMinutes', IntegerType::class, [
                'label'       => 'mautic.cron.orchestrator.form.frequency',
                'label_attr'  => ['class' => 'control-label'],
                'attr'        => ['class' => 'form-control'],
                'required'    => true,
                'empty_data'  => 0,
                'constraints' => [
                    new GreaterThan([
                        'value'   => 0,
                        'message' => 'mautic.cron.orchestrator.form.frequency.invalid',
                    ]),
                ],
            ])
            ->add('isEnabled', YesNoButtonGroupType::class, [
                'label'     => 'mautic.cron.orchestrator.form.enabled',
                'yes_value' => true,
                'no_value'  => false,
            ])
            ->add('buttons', FormButtonsType::class)
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $this->isFormCancelled($form)) {
            return $this->redirectToRoute('mautic_cron_orchestrator_index');
        }

        if ($form->isSubmitted() && $form->isValid()) {
            $job->setModifiedAt(new DateTimeImmutable());
            $repo->save($job);

            if ($this->isFormApplied($form)) {
                return $this->redirectToRoute('mautic_cron_orchestrator_edit', [
                    'objectId' => $job->getId(),
                ]);
            }

            return $this->redirectToRoute('mautic_cron_orchestrator_index');
        }

        return $this->delegateView([
            'viewParameters' => [
                'job'         => $job,
                'is_new'      => $isNew,
                'recent_logs' => $recentLogs,
                'form'        => $form->createView(),
            ],
            'contentTemplate' => '@MauticCronOrchestratorBundle/Cron/edit.html.twig',
            'passthroughVars' => [
                'activeLink' => '#mautic_cron_orchestrator_index',
                'route'      => $this->generateUrl('mautic_cron_orchestrator_edit', ['objectId' => $objectId]),
            ],
        ]);
    }

    public function toggleAction(Config $config, int|string $objectId): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        $repo = $this->cronJobRepository();
        $job = $repo->find((int) $objectId);

        if (null !== $job) {
            $job->setIsEnabled(!$job->isEnabled());
            $job->setModifiedAt(new DateTimeImmutable());
            $repo->save($job);
        }

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
    }

    public function runNowAction(Config $config, int|string $objectId, CronOrchestratorModel $orchestrator): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        $repo = $this->cronJobRepository();
        $job = $repo->find((int) $objectId);

        if (null !== $job) {
            $orchestrator->runJobNow($job);
        }

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
    }

    public function deleteAction(Config $config, int|string $objectId): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        $repo = $this->cronJobRepository();
        $job = $repo->find((int) $objectId);

        if (null !== $job) {
            $repo->delete($job);
        }

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
    }

    public function batchDeleteAction(Request $request, Config $config): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        if ($request->isMethod('POST')) {
            $count = $this->cronJobRepository()->deleteByIds($this->parseBatchIds($request));
            if ($count > 0) {
                $this->addFlashMessage('mautic.cron.orchestrator.notice.batch_deleted', ['%count%' => $count]);
            }
        }

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
    }

    public function batchEnableAction(Request $request, Config $config): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        if ($request->isMethod('POST')) {
            $count = $this->cronJobRepository()->setEnabledByIds($this->parseBatchIds($request), true);
            if ($count > 0) {
                $this->addFlashMessage('mautic.cron.orchestrator.notice.batch_enabled', ['%count%' => $count]);
            }
        }

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
    }

    public function batchDisableAction(Request $request, Config $config): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        if ($request->isMethod('POST')) {
            $count = $this->cronJobRepository()->setEnabledByIds($this->parseBatchIds($request), false);
            if ($count > 0) {
                $this->addFlashMessage('mautic.cron.orchestrator.notice.batch_disabled', ['%count%' => $count]);
            }
        }

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
    }

    public function applyPresetAction(Config $config, string $preset, CronOrchestratorModel $orchestrator): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        if (!\in_array($preset, ['minimal', 'standard', 'full', 'custom'], true)) {
            return $this->redirectToRoute('mautic_cron_orchestrator_index');
        }

        $orchestrator->seedPreset($preset);

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
    }

    /**
     * @return int[]
     */
    private function parseBatchIds(Request $request): array
    {
        $ids = json_decode((string) $request->query->get('ids', '[]'), true);
        if (!\is_array($ids)) {
            return [];
        }

        return array_values(array_unique(array_filter(array_map('intval', $ids))));
    }

    private function cronJobRepository(): CronJobRepository
    {
        $repo = $this->getDoctrine()->getRepository(CronJob::class);
        \assert($repo instanceof CronJobRepository);

        return $repo;
    }

    private function cronJobLogRepository(): CronJobLogRepository
    {
        $repo = $this->getDoctrine()->getRepository(CronJobLog::class);
        \assert($repo instanceof CronJobLogRepository);

        return $repo;
    }
}
