<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Output\OutputInterface;

trait OutputStyleTrait
{
    /**
     * Add styles corresponding to error levels.
     */
    public function setStyle(OutputInterface $output)
    {
        // https://symfony.com/doc/current/console/coloring.html
        foreach ([
                     'notice' => new OutputFormatterStyle('blue'),
                     'warning' => new OutputFormatterStyle(null, 'yellow'),
                     'debug' => new OutputFormatterStyle(null, 'magenta'),
                 ] as $name => $style) {
            $output->getFormatter()->setStyle($name, $style);
        }
    }
}
