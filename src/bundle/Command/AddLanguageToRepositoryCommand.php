<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\API\Repository\LanguageService;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class AddLanguageToRepositoryCommand extends AdminCommandAbstract
{
    protected static $defaultName = 'ezplatform:language:add';

    /** @var LanguageService */
    private $languageService;

    protected function configure(): void
    {
        parent::configure();
        $this->setDescription('Create a new language')
            ->addArgument('code', InputArgument::REQUIRED, 'Language code as <ISO 639‑2/B>-<ISO 3166-1 alpha-2>, in other words <three_lowercase_language>-<two_uppercase_country>; Examples: eng-GB, eng-US, fre-FR, fre-CA')
            ->addArgument('name', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Language name; Traditionally as full language name in itself followed by country in parentheses; Examples: English (United Kingdom), English (United States), Français (France), Français (Canada)');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $this->languageService = $this->repository->getContentLanguageService();

        $initCode = $this->initAdminFunctionExecution($input, $output);

        if (self::SUCCESS === $initCode) {
            return $this->executeAdminFunction(function () {
                $languageCreateStruct = $this->languageService->newLanguageCreateStruct();
                $languageCreateStruct->languageCode = $this->input->getArgument('code');
                $languageCreateStruct->name = implode(' ', $this->input->getArgument('name'));
                $languageCreateStruct->enabled = true;
                $this->languageService->createLanguage($languageCreateStruct);

                return self::SUCCESS;
            });
        } else {
            return $initCode;
        }
    }
}
