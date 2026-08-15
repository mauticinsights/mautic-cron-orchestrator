<?php

declare(strict_types=1);

namespace MauticPlugin\MauticCronOrchestratorBundle\Command;

use Mautic\CoreBundle\Helper\CoreParametersHelper;
use MauticPlugin\MauticCronOrchestratorBundle\Model\CronOrchestratorModel;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CronRunCommand extends Command
{
    protected static $defaultName = 'mautic:cron:run';

    public function __construct(
        private CronOrchestratorModel $orchestrator,
        private CoreParametersHelper $coreParametersHelper,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setDescription('Run all due cron jobs managed by the Cron Orchestrator.')
            ->addOption('cleanup-only', null, InputOption::VALUE_NONE, 'Only purge old log entries, do not run any jobs.')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Show which jobs are due without executing them.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->coreParametersHelper->get('cron_orchestrator_enabled', true)) {
            $io->note('Cron orchestrator is disabled (cron_orchestrator_enabled=false).');

            return Command::SUCCESS;
        }

        if ($input->getOption('cleanup-only')) {
            $deleted = $this->orchestrator->deleteOldLogs();
            $io->success("Cleaned up {$deleted} old log entries.");

            return Command::SUCCESS;
        }

        if ($input->getOption('dry-run')) {
            $due = $this->orchestrator->findDueJobs();
            if ([] === $due) {
                $io->note('No due jobs found.');

                return Command::SUCCESS;
            }

            $io->title(sprintf('Dry-run: %d due job(s) would be executed', count($due)));
            $io->table(
                ['Name', 'Command', 'Frequency', 'Last run'],
                array_map(fn ($job) => [
                    $job->getName(),
                    $job->getCommand().(null !== $job->getArguments() && '' !== trim($job->getArguments()) ? ' '.$job->getArguments() : ''),
                    "{$job->getFrequencyMinutes()} min",
                    null !== $job->getLastRunAt() ? $job->getLastRunAt()->format('Y-m-d H:i') : 'never',
                ], $due),
            );

            return Command::SUCCESS;
        }

        $stats = $this->orchestrator->runDueJobs();

        $io->success(sprintf(
            'Done: %d run, %d skipped (not due or already running), %d errors.',
            $stats['run'],
            $stats['skipped'],
            $stats['errors'],
        ));

        return $stats['errors'] > 0 ? Command::FAILURE : Command::SUCCESS;
    }
}
