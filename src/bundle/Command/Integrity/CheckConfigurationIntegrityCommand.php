<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\Integrity;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckConfigurationIntegrityCommand extends CheckIntegrityCommandAbstract
{
    protected static $defaultName = 'integrity:check:config';

    protected function configure()
    {
        $this
            ->setDescription('Check PHP and DXP configuration consistency.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $result = $this->integrityService->checkUploadMaxFileSize();
        foreach ($result['warnings'] as $warning) {
            $output->writeln("<warning>$warning</warning>");
        }
        foreach ($result['errors'] as $error) {
            $output->writeln("<error>$error</error>");
        }

        return count($result['errors']) ? self::ERROR : (count($result['warnings']) ? self::WARNING : self::SUCCESS);
    }
}
