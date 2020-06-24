<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckLanguageIntegrityCommand extends Command
{
    protected static $defaultName = 'integrity:check:language';

    public const SUCCESS = 0;
    public const WARNING = 1;
    public const ERROR = 2;

    /** @var IntegrityService */
    private $integrityService;

    public function __construct(IntegrityService $integrityService)
    {
        parent::__construct(self::$defaultName);
        $this->integrityService = $integrityService;
    }

    protected function configure()
    {
        $this
            ->setDescription('Check language configuration.')//TODO: and language usage
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('siteaccess')) {
            $output->writeln('<comment>An admin siteaccess should be provided (using --siteaccess option) instead of falling back on default one.</comment>');
        }

        $status = 0;

        $availableAndMissingLanguages = $this->integrityService->getAvailableAndMissingLanguages();
        foreach ($availableAndMissingLanguages['missing_languages']['from_config'] as $languageCode) {
            $output->writeln("<error>'$languageCode' is missing from configuration while available in back-office.</error>");
            $status |= self::ERROR;
        }
        foreach ($availableAndMissingLanguages['missing_languages']['from_database'] as $languageCode) {
            $output->writeln("<comment>'$languageCode' is missing from repository while declared in configuration.</comment>");
            $status |= self::WARNING;
        }
        foreach ($this->integrityService->getUnknownLanguages() as $unknownLanguageId) {
            $output->writeln("<error>A language with ID $unknownLanguageId is used but missing from repository's language list.</error>");
            $status |= self::ERROR;
        }
        if (!$status) {
            $output->writeln('<info>Language setting is alright.</info>');
        }

        return $status;
    }
}
