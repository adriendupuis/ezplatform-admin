<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Command;

use AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Formatter\OutputFormatter;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;

abstract class CheckIntegrityCommandAbstract extends Command
{
    //protected static $defaultName = 'integrity:check:*';

    // Information levels / Exit codes
    public const SUCCESS = 0; // When there is no problem
    public const NOTICE = 1; // When there is anomalies that can be fixed later or left like this
    public const WARNING = 2; // When there is anomalies that should be fixed as soon as possible
    public const ERROR = 4; // When there is anomalies that should be fixed right now

    /** @var IntegrityService */
    protected $integrityService;

    public function __construct(IntegrityService $integrityService)
    {
        parent::__construct(self::$defaultName);
        $this->integrityService = $integrityService;
    }

    public function setStyles(OutputFormatter $outputFormatter)
    {
        $outputFormatter->setStyle('success', new OutputFormatterStyle('white', 'green'));
        $outputFormatter->setStyle('notice', new OutputFormatterStyle('white', 'blue'));
        $outputFormatter->setStyle('warning', new OutputFormatterStyle('white', 'magenta'));
        $outputFormatter->setStyle('error', new OutputFormatterStyle('white', 'red'));
    }

    public function getLevelName(int $level)
    {
        if (self::ERROR & $level) {
            return 'error';
        }
        if (self::WARNING & $level) {
            return 'warning';
        }
        if (self::NOTICE & $level) {
            return 'notice';
        }

        return 'success';
    }
}
