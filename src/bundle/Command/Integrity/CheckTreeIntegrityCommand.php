<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command\Integrity;

use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

class CheckTreeIntegrityCommand extends CheckIntegrityCommandAbstract
{
    protected static $defaultName = 'integrity:check:tree';

    protected function configure()
    {
        $this
            ->setDescription('Check content tree locations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $locations = [];

        foreach ([
                     'content' => $this->integrityService->getMissingContentLocations(),
                     'parent' => $this->integrityService->getMissingParentLocations(),
                 ] as $whatIsMissing => $missingSomethingLocations) {
            foreach ($missingSomethingLocations as $missingSomethingLocation) {
                if (array_key_exists($missingSomethingLocation['node_id'], $locations)) {
                    $locations[$missingSomethingLocation['node_id']]['missing'][] = $whatIsMissing;
                } else {
                    $missingSomethingLocation['missing'] = [$whatIsMissing];
                    $locations[$missingSomethingLocation['node_id']] = $missingSomethingLocation;
                }
            }
        }

        foreach ($locations as $location) {
            if (in_array('content', $location['missing'])) {
                if (in_array('parent', $location['missing'])) {
                    $output->writeln("<warning>location {$location['node_id']} misses both its content {$location['contentobject_id']} and its parent location {$location['parent_node_id']}.</warning>");
                } else {
                    $output->writeln("<warning>location {$location['node_id']} misses both its content {$location['contentobject_id']}.</warning>");
                }
            } else /* if (in_array('parent', $location['missing'])) */ {
                $output->writeln("<warning>location {$location['node_id']} misses its parent location {$location['parent_node_id']}.</warning>");
            }
        }

        if (count($locations)) {
            $output->writeln('<info>Location with missing content and/or parent won\'t break the whole platform but cause some broken URLs.</info>');
            //TODO: How to fix those locations?
            return self::WARNING;
        }

        return self::SUCCESS;
    }
}
