<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Controller;

use DateTimeImmutable;
use Mautic\CoreBundle\Controller\FormController;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJob;
use MauticPlugin\MauticCronOrchestratorBundle\Entity\CronJobLog;
use MauticPlugin\MauticCronOrchestratorBundle\Integration\Config;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobLogRepository;
use MauticPlugin\MauticCronOrchestratorBundle\Repository\CronJobRepository;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Mautic admin UI for managing cron jobs.
 *
 * Unit tests not feasible in standalone mode — depends on Mautic FormController
 * ($this->security, $this->getDoctrine(), $this->delegateView(), $this->createFormBuilder()).
 * Tested via manual verification in a running Mautic instance.
 */
class CronController extends FormController
{
    public function indexAction(Config $config): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        $repo = $this->cronJobRepository();
        $jobs = $repo->findBy([], ['id' => 'ASC']);

        return $this->delegateView([
            'viewParameters' => [
                'jobs' => $jobs,
                'presets' => [
                    'minimal' => CronOrchestratorModel::getPresetJobs('minimal'),
                    'standard' => CronOrchestratorModel::getPresetJobs('standard'),
                    'full' => CronOrchestratorModel::getPresetJobs('full'),
                ],
            ],
            'contentTemplate' => '@MauticCronOrchestratorBundle/Cron/index.html.twig',
            'passthroughVars' => [
                'activeLink' => '#mautic_cron_orchestrator_index',
                'route' => $this->generateUrl('mautic_cron_orchestrator_index'),
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
        }

        $action = $this->generateUrl('mautic_cron_orchestrator_edit', ['objectId' => $objectId]);

        $form = $this->createFormBuilder($job, ['action' => $action])
            ->add('name', TextType::class, [
                'label' => 'mautic.cron.orchestrator.form.name',
                'required' => true,
            ])
            ->add('command', TextType::class, [
                'label' => 'mautic.cron.orchestrator.form.command',
                'required' => true,
            ])
            ->add('arguments', TextType::class, [
                'label' => 'mautic.cron.orchestrator.form.arguments',
                'required' => false,
            ])
            ->add('frequencyMinutes', IntegerType::class, [
                'label' => 'mautic.cron.orchestrator.form.frequency',
                'required' => true,
            ])
            ->add('isEnabled', CheckboxType::class, [
                'label' => 'mautic.cron.orchestrator.form.enabled',
                'required' => false,
            ])
            ->add('save', SubmitType::class, [
                'label' => 'mautic.cron.orchestrator.form.save',
            ])
            ->getForm();

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $job->setModifiedAt(new DateTimeImmutable());
            $repo->save($job);

            return $this->redirectToRoute('mautic_cron_orchestrator_index');
        }

        return $this->delegateView([
            'viewParameters' => [
                'job' => $job,
                'is_new' => $isNew,
                'recent_logs' => $recentLogs,
                'form' => $form->createView(),
            ],
            'contentTemplate' => '@MauticCronOrchestratorBundle/Cron/edit.html.twig',
            'passthroughVars' => [
                'activeLink' => '#mautic_cron_orchestrator_index',
                'route' => $this->generateUrl('mautic_cron_orchestrator_edit', ['objectId' => $objectId]),
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

    public function applyPresetAction(Config $config, string $preset, CronOrchestratorModel $orchestrator): Response
    {
        if (!$this->security->isAdmin() || !$config->isPublished()) {
            return $this->accessDenied();
        }

        if (!\in_array($preset, ['minimal', 'standard', 'full'], true)) {
            return $this->redirectToRoute('mautic_cron_orchestrator_index');
        }

        $orchestrator->seedPreset($preset);

        return $this->redirectToRoute('mautic_cron_orchestrator_index');
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
