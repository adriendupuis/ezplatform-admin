<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\Integrity;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckIntegrityCommand extends CheckIntegrityCommandAbstract
{
    protected static $defaultName = 'integrity:check';

    protected function configure()
    {
        $this
            ->setDescription('Launch all available integrity checks (but won\'t fix anything and will just report eventual problems.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setStyles($output->getFormatter());
        $symfonyStyle = new SymfonyStyle($input, $output);

        $exitCode = self::SUCCESS;

        /** @var Command $command */
        foreach ($this->getApplication()->all(self::$defaultName) as $index => $command) {
            if ($index) {
                $symfonyStyle->newLine();
            }
            $symfonyStyle->section('Run '.$command->getName());
            $exitCode |= $command->run($input, $output);
        }

        $symfonyStyle->newLine();
        $symfonyStyle->section('Summary');
        if ($exitCode) {
            $levelName = $this->getLevelName($exitCode);
            $output->writeln("<$levelName>Higher problem level: $levelName</$levelName>");
        } else {
            $output->writeln('<success>Every thing is alright.</success>');
        }

        return $exitCode;
    }
}
