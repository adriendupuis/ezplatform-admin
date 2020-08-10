<?php

use AdrienDupuis\EzPlatformAdminBundle\Service\SearchEngineMonitorServiceAbstract;
use PHPUnit\Framework\TestCase;

class SearchEngineMonitorServiceAbstractTest extends TestCase
{
    public function testBinaryFormatBytes()
    {
        $this->assertEquals(
            '1.00 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(2 ** 10)
        );
        $this->assertEquals(
            '1 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(2 ** 10, 0)
        );
        $this->assertEquals(
            '1.00 MiB',
            SearchEngineMonitorServiceAbstract::formatBytes(2 ** 20)
        );
        $this->assertEquals(
            '1024.00 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(2 ** 20, 2, 'KiB')
        );
        $this->assertEquals(
            '1.50 GiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1.5 * 2 ** 30)
        );
    }

    public function testMetricFormatBytes()
    {
        $this->assertEquals(
            '1.00 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(10 ** 3, 2, null, 1000)
        );
        $this->assertEquals(
            '1 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(10 ** 3, 0, null, 1000)
        );
        $this->assertEquals(
            '1.00 MB',
            SearchEngineMonitorServiceAbstract::formatBytes(10 ** 6, 2, null, 1000)
        );
        $this->assertEquals(
            '1000.00 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(10 ** 6, 2, 'kB', 1000)
        );
        $this->assertEquals(
            '1.50 GB',
            SearchEngineMonitorServiceAbstract::formatBytes(1.5 * 10 ** 9, 2, null, 1000)
        );
    }
}
