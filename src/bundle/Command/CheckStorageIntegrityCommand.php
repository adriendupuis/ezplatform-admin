<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService;
use eZ\Publish\API\Repository\ContentTypeService;
use eZ\Publish\API\Repository\Values\Content\Content;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * @todo storage/images-versioned
 */
class CheckStorageIntegrityCommand extends Command
{
    protected static $defaultName = 'integrity:check:storage';

    const SUCCESS = 0;

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
            ->setDescription('Check storage integrity (search unused and missing files.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $status = self::SUCCESS;

        // Files in storage but not used
        $status |= $this
            ->getApplication()
            ->find(RemoveUnusedFilesFromStorageCommand::getDefaultName())
            ->run(new ArrayInput([
                '--dry-run' => true,
                '--siteaccess' => $input->getOption('siteaccess'),
            ]), $output);

        // Files missing from storage
        foreach ($this->integrityService->findMissingImageFiles() as $contentWithMissingImageFile) {
            /** @var Content $content */
            $content = $contentWithMissingImageFile['content'];
            $output->writeln("Content “{$content->getName()}” (id: ({$contentWithMissingImageFile['id']}; v: {$contentWithMissingImageFile['version']}; lang: {$contentWithMissingImageFile['language_code']}): ");
            foreach ($contentWithMissingImageFile['fields_with_wissing_image'] as $fieldIndentifier) {
                $fieldName = $content->getContentType()->getFieldDefinition($fieldIndentifier)->getName($contentWithMissingImageFile['language_code']);
                $fileName = $content->getField($fieldIndentifier)->value->fileName;
                $output->writeln("\t$fieldName ($fieldIndentifier): $fileName is missing.");
            }
        }

        return $status;
    }
}
