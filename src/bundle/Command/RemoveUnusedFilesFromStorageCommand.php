<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveUnusedFilesFromStorageCommand extends Command
{
    protected static $defaultName = 'ezplatform:storage:remove-unused-files';

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
            ->setDescription('Remove unused files (images and binaries) from storage.')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not remove anything, just list what could be removed.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!$input->getOption('siteaccess')) {
            $output->writeln('<comment>A siteaccess should be provided (using --siteaccess option) instead of falling back on default one.</comment>');
        }

        $status = 0;
        $status |= $this->cleanImages($input, $output);
        $status |= $this->cleanBinaries($input, $output);

        return $status;
    }

    private function cleanImages(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->integrityService->findUnusedImageDirectories() as $dirPath) {
            if (!$output->isQuiet()) {
                $output->write("$dirPath is not used");
            }
            if ($input->getOption('dry-run')) {
                if (!$output->isQuiet()) {
                    $output->writeln('.');
                }
            } else {
                if (!$output->isQuiet()) {
                    $output->writeln("; Remove {$dirPath} and its aliases…");
                }
                shell_exec('rm -r'.($output->isVerbose() ? 'v' : '')."f $dirPath "
                    .str_replace('/images/', '/images/_aliases/*/', $dirPath));
            }
        }

        return 0;
    }

    private function cleanBinaries(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->integrityService->findUnusedApplicationFiles() as $filePath) {
            $output->write("$filePath is not used");
            if ($input->getOption('dry-run')) {
                if (!$output->isQuiet()) {
                    $output->writeln('.');
                }
            } else {
                if (!$output->isQuiet()) {
                    $output->writeln("; Remove {$filePath}…");
                }
                shell_exec('rm -'.($output->isVerbose() ? 'v' : '')."f $filePath");
            }
        }

        return 0;
    }

    private function getPathListFromCmd($cmd)
    {
        $pathList = explode(PHP_EOL, trim(shell_exec($cmd)));
        if (count($pathList) && $pathList[0]) {
            return $pathList;
        }

        return [];
    }
}
