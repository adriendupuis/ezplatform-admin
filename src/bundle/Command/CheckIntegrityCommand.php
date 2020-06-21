<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIntegrityCommand extends Command
{
    protected static $defaultName = 'integrity:check';

    public $otherNameSpaceCommandNameList = [
        'ezplatform:storage:remove-unused-files',
    ]; //TODO: Maybe use a service tag or a parameter?

    protected function configure()
    {
        $this
            ->setDescription('Launch all available integrity checks (but won\'t fix anything and will just report eventual problems.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = 0;

        /** @var Command $command */
        foreach ($this->getApplication()->all(self::$defaultName) as $command) {
            $output->writeln('Run '.$command->getName());
            $status |= $command->run($input, $output);
        }
        foreach ($this->otherNameSpaceCommandNameList as $commandName) {
            $command = $this->getApplication()->find($commandName);
            $output->writeln("Run {$command->getName()} (dry run)");
            $parameters = [];
            if ($input->hasOption('siteaccess')) {
                $parameters['--siteaccess'] = $input->getOption('siteaccess');
            }
            if ($command->getDefinition()->hasOption('dry-run')) {
                $parameters['--dry-run'] = true;
            }
            $status |= $command->run(new ArrayInput($parameters), $output);
        }

        if (!$status) {
            $output->writeln('<info>Globaly, every thing is alright.</info>');
        }

        return $status;
    }
}
