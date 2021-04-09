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

    public function __construct(SearchService $searchService)
    {
        parent::__construct(self::$defaultName);
        $this->searchService = $searchService;
    }

    public function configure()
    {
        $this
            ->setDescription('Search for content using fulltext')
            //->addOption('offset', 'o',InputOption::VALUE_REQUIRED, 'Returned content offset', 0)
            ->addOption('limit', 'c', InputOption::VALUE_REQUIRED, 'Returned content count', 5)
            ->addOption('language', 'l', InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY, 'Language to search in')
            ->addArgument('phrase', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'Searched text');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $phrase = implode(' ', $input->getArgument('phrase'));
        $languageFilter = ['languages' => $input->getOption('language')];
        $limit = $input->getOption('limit');
        $plural = 1 < $limit ? 's' : '';
        $output->writeln("Searching {$limit} first content{$plural} for “{$phrase}”…");

        try {
            $searchResult = $this->searchService->findContent(new Query([
                'filter' => new Query\Criterion\FullText($phrase),
                'limit' => $limit,
                //'sortClauses' => [new Query\SortClause\Score()],
            ]), $languageFilter);
        } catch (\Exception $e) {
            $output->writeln("<error>{$e->getMessage()}</error>");

            return 1;
        }

        $totalCount = $searchResult->totalCount;
        $sliceCount = count($searchResult->searchHits);
        $plural = 1 < $totalCount ? 's' : '';
        $time = $searchResult->time;
        $time = $time < 1 ? number_format(1000 * $time, 0, '.', '').'ms' : number_format($time, 3, '.', '').'s';
        $output->writeln("{$sliceCount}/{$totalCount} result{$plural} in $time");

        $canScore = !empty($searchResult->maxScore);

        foreach ($searchResult->searchHits as $searchHit) {
            /** @var Content $content */
            $content = $searchHit->valueObject;
            $score = $canScore ? (100 * $searchHit->score / $searchResult->maxScore).'% ' : '';
            $output->writeln("- [{$content->id}@{$content->contentInfo->mainLocationId}] {$score}“{$content->getName()}”");
        }

        return 0;
    }
}
