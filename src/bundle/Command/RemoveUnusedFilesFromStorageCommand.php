<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class RemoveUnusedFilesFromStorageCommand extends Command
{
    protected static $defaultName = 'ezplatform:storage:remove-unused-files';

    /** @var Connection */
    private $dbalConnection;

    /** @var string */
    private $imageDirFindCmd = 'find var/site/storage/images -mindepth 5 -type d;';

    /** @var QueryBuilder */
    private $imageQueryBuilder;

    /** @var string */
    private $imageAttributePattern = '% dirpath=":dirpath" %';

    /** @var string */
    private $binaryFileFindCmd = 'find var/site/storage/original/application -type f;';

    /** @var QueryBuilder */
    private $binaryQueryBuilder;

    public function __construct(Connection $connection)
    {
        parent::__construct();
        $this->dbalConnection = $connection;

        $this->imageQueryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('a.id, a.contentobject_id, a.version')
            ->from('ezcontentobject_attribute', 'a')
            ->where('a.data_text LIKE :dirpath');

        $this->binaryQueryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('a.id, a.contentobject_id, a.version')
            ->from('ezbinaryfile', 'f')
            ->leftJoin('f', 'ezcontentobject_attribute', 'a', 'f.contentobject_attribute_id = a.id')
            ->where('f.filename = :filename');
    }

    protected function configure()
    {
        $this
            ->setDescription('Remove unused files (images and binaries) from storage.')
            ->addOption('dry-run', 'd', InputOption::VALUE_NONE, 'Do not remove anything, just list what could be removed.')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (chdir('public')) {
            if ($imageCleaningStatus = $this->cleanImages($input, $output)) {
                return $imageCleaningStatus;
            }

            return $this->cleanBinaries($input, $output);
        } else {
            return 1;
        }
    }

    private function cleanImages(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getPathListFromCmd($this->imageDirFindCmd) as $dirPath) {
            /** @var array|bool $usage */
            $usage = $this->imageQueryBuilder
                ->setParameter(':dirpath', str_replace(':dirpath', $dirPath, $this->imageAttributePattern))
                ->execute()
                ->fetch()
            ;
            if (false === $usage) {
                $output->writeln("Remove unused $dirPath");
                if (!$input->getOption('dry-run')) {
                    shell_exec("rm -rf $dirPath");
                }
            }
        }

        return 0;
    }

    private function cleanBinaries(InputInterface $input, OutputInterface $output): int
    {
        foreach ($this->getPathListFromCmd($this->binaryFileFindCmd) as $filePath) {
            $fileName = basename($filePath);
            /** @var array|bool $usage */
            $usage = $this->binaryQueryBuilder
                ->setParameter(':filename', $fileName)
                ->execute()
                ->fetch()
            ;
            if (false === $usage) {
                $output->writeln("Remove unused $filePath");
                if (!$input->getOption('dry-run')) {
                    shell_exec("rm -f $filePath");
                }
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
