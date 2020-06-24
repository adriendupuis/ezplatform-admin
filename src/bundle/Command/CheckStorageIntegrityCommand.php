<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\API\Repository\Values\Content\Content;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @todo storage/images-versioned
 */
class CheckStorageIntegrityCommand extends CheckIntegrityCommandAbstract
{
    protected static $defaultName = 'integrity:check:storage';

    protected function configure()
    {
        $this
            ->setDescription('Check storage integrity (search unused and missing files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $exitCode = self::SUCCESS;

        $exitCode |= $this->checkUnusedFiles($output);
        $exitCode |= $this->checkMissingFiles($output);

        return $exitCode;
    }

    private function checkUnusedFiles(OutputInterface $output): int
    {
        $exitCode = self::SUCCESS;

        foreach ($this->integrityService->findUnusedImageDirectories() as $dirPath) {
            $exitCode |= self::NOTICE;
            $output->writeln("<notice>$dirPath is not used.</notice>");
        }
        foreach ($this->integrityService->findUnusedApplicationFiles() as $filePath) {
            $exitCode |= self::NOTICE;
            $output->writeln("<notice>$filePath is not used.</notice>");
        }

        return $exitCode;
    }

    private function checkMissingFiles(OutputInterface $output): int
    {
        $this->setStyles($output->getFormatter());

        $exitCode = self::SUCCESS;

        $contentsWithMissingFile = $this->integrityService->findMissingFiles();

        foreach ($contentsWithMissingFile as $key => $contentData) {
            /* @var Content $content */
            $content = $contentData['content'];

            $exitCode |= $content->versionInfo->isArchived() ? self::WARNING : self::ERROR;
            $tagName = $this->getLevelName($exitCode);

            $output->writeln("Content “{$content->getName()}” (id: {$content->id}; v: {$contentData['version']}; lang: {$contentData['language_code']}): ");
            foreach ($contentData['fields_with_missing_file'] as $fieldData) {
                $fieldName = $content->getContentType()->getFieldDefinition($fieldData['identifier'])->getName($contentData['language_code']);
                $output->writeln("\t$fieldName ({$fieldData['identifier']}): <{$tagName}>{$fieldData['missing_path']} ({$fieldData['original_filename']}) is missing.</{$tagName}>");
            }
        }

        return $exitCode;
    }
}
