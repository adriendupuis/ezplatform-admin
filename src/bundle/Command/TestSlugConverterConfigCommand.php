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

    public const INTERNAL_TRANSFORMATION = 'cli_tested_tranformation_group';

    /** @var TransformationProcessor */
    private $transformationProcessor;

    /** @var SlugConverter */
    private $slugConverter;

    public function __construct(TransformationProcessor $transformationProcessor, SlugConverter $slugConverter)
    {
        parent::__construct(self::$defaultName);
        $this->transformationProcessor = $transformationProcessor;
        $this->slugConverter = $slugConverter;
    }

    protected function configure(): void
    {
        $this->setDescription('Test a Slug Converter config')
            ->addOption('separator', 's', InputOption::VALUE_REQUIRED, 'Decides what separator is used. There are three types of separator available: dash, underscore and space.')
            ->addOption('transformation', 't', InputOption::VALUE_REQUIRED, 'Already configured transformation identifier')
            ->addOption('transformation_group', 'g', InputOption::VALUE_REQUIRED, 'Transformation group config as JSON; Example \'{"commands": ["endline_search_normalize"], "cleanup_method": "url_cleanup"}\'')
            ->addOption('dump_config', 'd', InputOption::VALUE_NONE, 'Dump computed configuration before convertion')
            ->addArgument('text', InputArgument::REQUIRED | InputArgument::IS_ARRAY, 'The text to convert into slug');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $text = implode(' ', $input->getArgument('text'));

        $configuration = $this->getSlugConverterConfiguration($this->slugConverter);
        $transformation = null;

        if ($transformationGroupOption = $input->getOption('transformation_group')) {
            $transformationGroup = json_decode($transformationGroupOption, true);
            if (array_key_exists('cleanup_method', $transformationGroup) && !array_key_exists('cleanupMethod', $transformationGroup)) {
                $transformationGroup['cleanupMethod'] = $transformationGroup['cleanup_method'];
            }

            $configuration['transformationGroups'][self::INTERNAL_TRANSFORMATION] = $transformationGroup;
            $transformation = self::INTERNAL_TRANSFORMATION;

            unset($transformationGroupOption, $transformationGroup);
        } elseif ($transformationOption = $input->getOption('transformation')) {
            if (array_key_exists($transformationOption, $configuration['transformationGroups'])) {
                $transformation = $transformationOption;
            } else {
                throw new \InvalidArgumentException('invalid transformation name');
            }
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

        if ($input->getOption('dump_config')) {
            dump($this->getSlugConverterConfiguration($slugConverter));
        }

        $output->writeln('<info>transformation: '.($transformation ? $transformation : $configuration['transformation']).'</info>');
        $output->writeln($slugConverter->convert($text, '', $transformation));

        return 0;
    }

    public function getSlugConverterConfiguration(SlugConverter $slugConverter)
    {
        $allowedClasses = [
            'AdrienDupuis\EzPlatformAdminBundle\Command\TestSlugConverterDecorator',
            //AdrienDupuis\EzPlatformAdminBundle\Command\TestSlugConverterDecorator::class,// As that class is created after this one, it can't be written like this.
            eZ\Publish\Core\Persistence\TransformationProcessor\PreprocessedBased::class,
            eZ\Publish\Core\Persistence\TransformationProcessor\PcreCompiler::class,
            eZ\Publish\Core\Persistence\Utf8Converter::class,
        ];

        $serializedSlugConverter = serialize($slugConverter);
        $serializedDecoratedSlugConverter = str_replace('O:65:"eZ\Publish\Core\Persistence\Legacy\Content\UrlAlias\SlugConverter"', 'O:69:"AdrienDupuis\EzPlatformAdminBundle\Command\TestSlugConverterDecorator"', $serializedSlugConverter);
        /** @var TestSlugConverterDecorator $decoratedSlugConverter */
        $decoratedSlugConverter = unserialize($serializedDecoratedSlugConverter, ['allowed_classes' => $allowedClasses]);

        return $decoratedSlugConverter->getConfiguration();
    }
}

class TestSlugConverterDecorator extends SlugConverter
{
    public function getConfiguration()
    {
        return $this->configuration;
    }
}
