<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\API\Repository\SearchService;
use eZ\Publish\API\Repository\Values\Content\Content;
use eZ\Publish\API\Repository\Values\Content\Query;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class FulltextSearchCommand extends Command
{
    protected static $defaultName = 'search:fulltext';

    function __construct(SearchService $searchService)
    {
        parent::__construct(self::$defaultName);
        $this->searchService = $searchService;
    }

    function configure()
    {
        $this
            ->setDescription('Search for content using fulltext')
            //->addOption('offset', 'o',InputOption::VALUE_REQUIRED, 'Returned content offset', 0)
            ->addOption('limit', 'c', InputOption::VALUE_REQUIRED, 'Returned content count', 5)
            //->addOption('language', 'l', InputOption::VALUE_REQUIRED, 'Language to search in')
            ->addArgument('phrase', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Searched text');
    }

    function execute(InputInterface $input, OutputInterface $output)
    {
        $phrase = implode(' ', $input->getArgument('phrase'));
        $limit = $input->getOption('limit');
        $plural = 1 < $limit ? 's' : '';
        $output->writeln("Searching {$limit} content{$plural} for “{$phrase}”…");

        try {
            $searchResult = $this->searchService->findContent(new Query([
                'filter' => new Query\Criterion\FullText($phrase),
                'limit' => $limit,
            ]));
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return 1;
        }

        $totalCount = $searchResult->totalCount;
        $plural = 1 < $totalCount ? 's' : '';
        $output->writeln("{$totalCount} result{$plural}");

        foreach ($searchResult->searchHits as $searchHit) {
            /** @var Content $content */
            $content = $searchHit->valueObject;
            $output->writeln("- [{$content->id}] “{$content->getName()}” (main location: {$content->contentInfo->mainLocationId})");
        }

        return 0;
    }
}