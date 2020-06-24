<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use eZ\Publish\API\Repository\LanguageService;
use eZ\Publish\API\Repository\Values\Content\Language;
use eZ\Publish\Core\IO\IOConfigProvider;
use eZ\Publish\Core\MVC\Symfony\SiteAccess;
use Symfony\Component\DependencyInjection\ContainerInterface;

class IntegrityService
{
    /** @var ContainerInterface */
    private $container;

    /** @var SiteAccess */
    private $siteAccess;

    /** @var Connection */
    private $dbalConnection;

    /** @var IOConfigProvider */
    private $ioConfigProvider;

    /** @var LanguageService */
    private $languageService;

    public function __construct(
        ContainerInterface $container,
        SiteAccess $siteAccess,
        Connection $connection,
        IOConfigProvider $ioConfigProvider,
        LanguageService $languageService
    ) {
        $this->container = $container;
        $this->siteAccess = $siteAccess;
        $this->dbalConnection = $connection;
        $this->ioConfigProvider = $ioConfigProvider;
        $this->languageService = $languageService;
    }

    public function getAvailableAndMissingLanguages(): array
    {
        $configLanguageCodeList = $this->container->getParameter("ezsettings.{$this->siteAccess->name}.languages");
        $databaseLanguageList = $this->languageService->loadLanguages();

        $languageMap = [];
        $databaseLanguageCodeList = [];
        /** @var Language $language */
        foreach ($databaseLanguageList as $language) {
            $languageMap[$language->languageCode] = $language;
            $databaseLanguageCodeList[] = $language->languageCode;
        }

        return [
            'language_map' => $languageMap,
            'available_languages' => array_intersect($configLanguageCodeList, $databaseLanguageCodeList),
            'missing_languages' => [
                'from_config' => array_diff($databaseLanguageCodeList, $configLanguageCodeList), // Error: using this language(s) in admin can cause fatal errors
                'from_database' => array_diff($configLanguageCodeList, $databaseLanguageCodeList), // Notice: won't be usable until created in admin but no fatal error
            ],
        ];
    }

    public function getUnknownLanguages(): array
    {
        $availableLanguageMask = $this->dbalConnection->createQueryBuilder()
            ->select('BIT_OR(l.id) AS available_language_mask')
            ->from('ezcontent_language', 'l')
            ->execute()
            ->fetchColumn(0)
        ;

        /** @var QueryBuilder $queryBuilder */
        $queryBuilder = $this->dbalConnection->createQueryBuilder();

        return $queryBuilder
            ->select(["DISTINCT (o.language_mask ^ $availableLanguageMask) AS unknown_language"])
            ->from('ezcontentobject', 'o')
            ->leftJoin('o', 'ezcontent_language', 'l', 'o.language_mask & l.id = l.id')
            ->where($queryBuilder->expr()->isNull('l.id'))
            ->execute()
            ->fetchAll()
        ;
    }

    /** @var string */
    private $imageAttributePattern = '% dirpath=":dirpath" %';

    /** @return string[] Array of image directory paths seeming unused and deletable */
    public function findUnusedImageDirectories(): array
    {
        $cmd = "find {$this->ioConfigProvider->getRootDir()}/images -mindepth 5 -type d 2> /dev/null;";
        $imageQueryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('a.id, a.contentobject_id, a.version')
            ->from('ezcontentobject_attribute', 'a')
            ->where('a.data_type_string = \'ezimage\'')
            ->andWhere('a.data_text LIKE :dirpath')
            ->setMaxResults(1)
        ;

        $unusedImageDirectories = [];
        foreach ($this->getPathListFromCmd($cmd) as $absoluteDirPath) {
            $dirPath = str_replace(trim(`pwd`).'/public/', '', $absoluteDirPath);
            /** @var array|bool $usage */
            $usage = $imageQueryBuilder
                ->setParameter(':dirpath', str_replace(':dirpath', $dirPath, $this->imageAttributePattern))
                ->execute()
                ->fetch()
            ;
            if (false === $usage) {
                $unusedImageDirectories[] = $absoluteDirPath;
            }
        }

        return $unusedImageDirectories;
    }

    /** @return string[] Array of binary file paths seeming unused and deletable */
    public function findUnusedApplicationFiles()
    {
        $cmd = "find {$this->ioConfigProvider->getRootDir()}/original/application -type f 2> /dev/null;";
        $binaryQueryBuilder = $this->dbalConnection->createQueryBuilder()
            ->select('a.id, a.contentobject_id, a.version')
            ->from('ezbinaryfile', 'f')
            ->leftJoin('f', 'ezcontentobject_attribute', 'a', 'f.contentobject_attribute_id = a.id')
            ->where('f.filename = :filename')
            ->setMaxResults(1)
        ;

        $unusedApplicationFiles = [];
        foreach ($this->getPathListFromCmd($cmd) as $filePath) {
            $fileName = basename($filePath);
            /** @var array|bool $usage */
            $usage = $binaryQueryBuilder
                ->setParameter(':filename', $fileName)
                ->execute()
                ->fetch()
            ;
            if (false === $usage) {
                $unusedApplicationFiles[] = $filePath;
            }
        }

        return $unusedApplicationFiles;
    }

    /** @todo Use a dedicated PHP class instead of an array to represent a content with missing file(s) */
    private function addFieldWithMissingFile(
        array &$contentsWithMissingFile,
        int $contentId,
        int $version,
        string $languageCode,
        string $fieldIdentifier,
        string $missingPath,
        string $originalFilename
    ): void {
        $key = "$contentId--$version--$languageCode";
        if (array_key_exists($key, $contentsWithMissingFile)) {
            $contentsWithMissingFile[$key]['fields_with_wissing_file'][] = $fieldIdentifier;
        } else {
            $contentsWithMissingFile[$key] = [
                'content' => $this->container->get('ezpublish.api.service.content')->loadContent($contentId, [$languageCode], $version), //TODO: dependecy injection
                'content_id' => $contentId,
                'version' => $version,
                'language_code' => $languageCode,
                'fields_with_missing_file' => [[
                    'identifier' => $fieldIdentifier,
                    'missing_path' => $missingPath,
                    'original_filename' => $originalFilename,
                ]],
            ];
        }
    }

    public function findMissingFiles()
    {
        $contentsWithMissingFile = [];
        $contentsWithMissingFile = $this->findMissingImageFiles($contentsWithMissingFile);
        $contentsWithMissingFile = $this->findMissingBinaryFiles($contentsWithMissingFile);

        return $contentsWithMissingFile;
    }

    /** @return array[] */
    public function findMissingImageFiles(?array $contentsWithMissingFile = null): array
    {
        $statement = $this->dbalConnection->createQueryBuilder()
            ->select('oa.id, oa.contentobject_id, oa.version, oa.language_code, ca.identifier, oa.data_text')
            ->from('ezcontentobject_attribute', 'oa')
            ->join('oa', 'ezcontentclass_attribute', 'ca', 'oa.contentclassattribute_id = ca.id')
            ->where('oa.data_type_string = \'ezimage\'')
            ->execute()
        ;

        if (!$contentsWithMissingFile) {
            $contentsWithMissingFile = [];
        }
        while ($row = $statement->fetch()) {
            preg_match('@dirpath="(?<dirpath>[^"]+)".*original_filename="(?<original_filename>[^"]+)"@s', $row['data_text'], $matches);
            if (!empty($matches['dirpath']) && !file_exists($dirPath = "{$this->container->getParameter('kernel.project_dir')}/public/{$matches['dirpath']}")) {//TODO: dependecy injection
                $this->addFieldWithMissingFile(
                    $contentsWithMissingFile,
                    $row['contentobject_id'],
                    $row['version'],
                    $row['language_code'],
                    $row['identifier'],
                    $dirPath,
                    $matches['original_filename'],
                );
            }
        }
        ksort($contentsWithMissingFile);

        return $contentsWithMissingFile;
    }

    /** @return array[] */
    public function findMissingBinaryFiles(?array $contentsWithMissingFile = null): array
    {
        $statement = $this->dbalConnection->createQueryBuilder()
            ->select('oa.id, oa.contentobject_id, oa.version, oa.language_code, ca.identifier, bf.filename, bf.original_filename')
            ->from('ezcontentobject_attribute', 'oa')
            ->join('oa', 'ezbinaryfile', 'bf', 'oa.id = bf.contentobject_attribute_id AND oa.version = bf.version')
            ->join('oa', 'ezcontentclass_attribute', 'ca', 'oa.contentclassattribute_id = ca.id')
            ->where('oa.data_type_string = \'ezbinaryfile\'')
            ->execute()
        ;

        if (!$contentsWithMissingFile) {
            $contentsWithMissingFile = [];
        }
        while ($row = $statement->fetch()) {
            if (!file_exists($filePath = "{$this->ioConfigProvider->getRootDir()}/original/application/{$row['filename']}")) {
                $this->addFieldWithMissingFile(
                    $contentsWithMissingFile,
                    $row['contentobject_id'],
                    $row['version'],
                    $row['language_code'],
                    $row['identifier'],
                    $filePath,
                    $row['original_filename'],
                );
            }
        }
        ksort($contentsWithMissingFile);

        return $contentsWithMissingFile;
    }

    /** @return string[] */
    private function getPathListFromCmd($cmd): array
    {
        $pathList = explode(PHP_EOL, trim(shell_exec($cmd)));

        if (count($pathList) && $pathList[0]) {
            return $pathList;
        }

        return [];
    }
}
