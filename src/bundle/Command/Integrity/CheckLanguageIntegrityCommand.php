<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\Integrity;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckLanguageIntegrityCommand extends CheckIntegrityCommandAbstract
{
    protected static $defaultName = 'integrity:check:language';

    protected function configure()
    {
        $this
            ->setDescription('Check language configuration.')//TODO: and language usage
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->setStyles($output->getFormatter());

        if (!$input->getOption('siteaccess')) {
            $output->writeln('<comment>An admin siteaccess should be provided (using --siteaccess option) instead of falling back on default one.</comment>');
        }

        $exitCode = self::SUCCESS;

        $availableAndMissingLanguages = $this->integrityService->getAvailableAndMissingLanguages();
        foreach ($availableAndMissingLanguages['missing_languages']['from_config'] as $languageCode) {
            $output->writeln("<error>'$languageCode' is missing from configuration while available in back-office.</error>");
            $exitCode |= self::ERROR;
        }
        foreach ($availableAndMissingLanguages['missing_languages']['from_database'] as $languageCode) {
            $output->writeln("<notice>'$languageCode' is missing from repository while declared in configuration.</notice>");
            $exitCode |= self::NOTICE;
        }
        foreach ($this->integrityService->getUnknownLanguages() as $unknownLanguageId) {
            $output->writeln("<error>A language with ID $unknownLanguageId is used but missing from repository's language list.</error>");
            $exitCode |= self::ERROR;
        }
        if (!$exitCode) {
            $output->writeln('<success>Language setting is alright.</success>');
        }

        return $exitCode;
    }
}
