<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder;
use eZ\Publish\API\Repository\Exceptions\NotImplementedException;
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

    public function findMissingImageFiles()
    {
        $cmd = "find {$this->ioConfigProvider->getRootDir()}/images -mindepth 5 -type d 2> /dev/null;";
        $statement = $this->dbalConnection->createQueryBuilder()
            ->select('oa.id, oa.contentobject_id, oa.version, oa.language_code, ca.identifier, oa.data_text')
            ->from('ezcontentobject_attribute', 'oa')
            ->join('oa', 'ezcontentclass_attribute', 'ca', 'oa.contentclassattribute_id = ca.id')
            ->where('oa.data_type_string = \'ezimage\'')
            ->execute()
        ;

        $contentsWithMissingImage = [];
        while($ezimageAttributeRow = $statement->fetch()) {
            preg_match('@dirpath="(?<dirpath>[^"]+)"@', $ezimageAttributeRow['data_text'], $matches);

            if (count($matches) && '' !== $matches['dirpath']) {
                if (!file_exists("./public/{$matches['dirpath']}")) {
                    $contentId = $ezimageAttributeRow['contentobject_id'];
                    $version = $ezimageAttributeRow['version'];
                    $languageCode = $ezimageAttributeRow['language_code'];
                    $fieldIdentifier = $ezimageAttributeRow['identifier'];
                    $key = "$contentId-$version-$languageCode";
                    if (array_key_exists($key, $contentsWithMissingImage)) {
                        $contentsWithMissingImage[$key]['fields_with_wissing_image'][] = $fieldIdentifier;
                    } else {
                        $contentsWithMissingImage[$key] = [
                            'content' => $this->container->get('ezpublish.api.service.content')->loadContent($contentId, [$languageCode], $version),//TODO: dependecy injection
                            'id' => $contentId,
                            'version' => $version,
                            'language_code' => $languageCode,
                            'fields_with_wissing_image' => [$fieldIdentifier],
                        ];
                    }
                }
            }
        }
        ksort($contentsWithMissingImage);
        return $contentsWithMissingImage;
    }

    public function findMissingBinaryFiles()
    {
        throw new NotImplementedException('Not yet implemented \AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService::findMissingBinaryFiles'); //TODO
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
