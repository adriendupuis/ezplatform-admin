<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\Integrity;

use AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveUnusedFilesFromStorageCommand extends Command
{
    protected static $defaultName = 'ezplatform:storage:remove-unused-files'; //TODO: integrity:fix:storage-unused-files?

    public const SUCCESS = 0; //TODO: When unused files are successfully removed or when there was nothing to remove?
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
            $output->write("$dirPath is not used");
            $aliasesDirPath = str_replace('/images/', '/images/_aliases/*/', $dirPath);
            if ($input->getOption('dry-run')) {
                if ($output->isVerbose()) {
                    $output->writeln(': ');
                    foreach (explode(PHP_EOL, shell_exec("find $dirPath -type f;")) as $filePath) {
                        if ($filePath) {
                            $output->writeln("$filePath could be removed.");
                        }
                    }
                    $output->writeln("$dirPath could be removed.");
                    foreach (explode(PHP_EOL, shell_exec("find ./public/var/ -path $dirPath;")) as $filePath) {
                        if ($filePath) {
                            $output->writeln("$filePath could be removed.");
                        }
                    }
                } else {
                    $output->writeln('; It could be removed as well as its aliases.');
                }
            } else {
                $output->writeln('; Remove it and its aliases…');
                $output->writeln(trim(shell_exec('rm -r'.($output->isVerbose() ? 'v' : '')."f $dirPath $aliasesDirPath;")));
            }
        }

        return 0;
    }

    private function cleanBinaries(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->integrityService->findUnusedApplicationFiles() as $filePath) {
            $output->write("$filePath is not used");
            if ($input->getOption('dry-run')) {
                $output->writeln('.');
            } else {
                $output->writeln("; Remove {$filePath}…");
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
