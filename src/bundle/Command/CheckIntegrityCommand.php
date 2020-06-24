<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckIntegrityCommand extends Command
{
    protected static $defaultName = 'integrity:check';

    public const SUCCESS = 0;

    protected function configure()
    {
        $this
            ->setDescription('Launch all available integrity checks (but won\'t fix anything and will just report eventual problems.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = self::SUCCESS;

        /** @var Command $command */
        foreach ($this->getApplication()->all(self::$defaultName) as $command) {
            $output->writeln('Run '.$command->getName());
            $status |= $command->run($input, $output);
        }

        if (!$status) {
            $output->writeln('<info>Globaly, every thing is alright.</info>');
        }

        return $status;
    }
}
