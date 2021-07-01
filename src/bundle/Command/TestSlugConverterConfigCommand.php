<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use eZ\Publish\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter;
use eZ\Publish\Core\Persistence\TransformationProcessor;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

class TestSlugConverterConfigCommand extends Command
{
    protected static $defaultName = 'ezplatform:slug:test';

    /** @var TransformationProcessor */
    private $transformationProcessor;

    public function __construct(TransformationProcessor $transformationProcessor)
    {
        parent::__construct(self::$defaultName);
        $this->transformationProcessor = $transformationProcessor;
    }

    protected function configure(): void
    {
        $this->setDescription('Test a Slug Converter config')
            ->addOption('separator', 's', InputOption::VALUE_REQUIRED, 'Decides what separator is used. There are three types of separator available: dash, underscore and space.')
            ->addOption('transformation', 't', InputOption::VALUE_REQUIRED, 'Already configured transformation identifier')
            ->addOption('transformation_group', 'g', InputOption::VALUE_REQUIRED, 'Transformation group config as JSON; Example \'{"commands": [], "cleanup_method": "url_cleanup"}\'')
            ->addArgument('text', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The text to convert into slug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $text = '';
        $configuration = [];
        $transformation = null;

        $text = implode(' ', $input->getArgument('text'));
        if ($transformationGroupOption = $input->getOption('transformation_group')) {
            $patternName = 'command_tested_tranformation_group';
            $transformationGroup = json_decode($transformationGroupOption, true);
            if (array_key_exists('cleanup_method', $transformationGroup) && !array_key_exists('cleanupMethod', $transformationGroup)) {
                $transformationGroup['cleanupMethod'] = $transformationGroup['cleanup_method'];
            }

            $configuration = [
                'transformationGroups' => [
                    $patternName => $transformationGroup,
                ],
            ];
            $transformation = $patternName;

            unset($transformationGroupOption, $transformationGroup);
        } elseif ($transformationOption = $input->getOption('transformation')) {
            $transformation = $transformationOption;
            unset($transformationOption);
        }

        if ($separator = $input->getOption('separator')) {
            if (in_array($separator, ['dash', 'underscore', 'space'])) {
                $configuration['wordSeparatorName'] = $separator;
            } else {
                throw new \InvalidArgumentException('invalid separator');
            }
        }

        $slugConverter = new SlugConverter($this->transformationProcessor, $configuration);

        $output->writeln($slugConverter->convert($text, '', $transformation));

        return 0;
    }
}
