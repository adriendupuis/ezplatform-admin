<?php

use AdrienDupuis\EzPlatformAdminBundle\Service\SearchEngineMonitorServiceAbstract;
use PHPUnit\Framework\TestCase;

class SearchEngineMonitorServiceAbstractTest extends TestCase
{
    public function testBinaryFormatBytes()
    {
        $this->assertEquals(
            '0.00 B',
            SearchEngineMonitorServiceAbstract::formatBytes(0)
        );
        $this->assertEquals(
            '0 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(0, 0, 'KiB')
        );

        $this->assertEquals(
            '1.00 B',
            SearchEngineMonitorServiceAbstract::formatBytes(1)
        );
        $this->assertEquals(
            '0 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1, 0, 'KiB')
        );
        $this->assertEquals(
            '0.001 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1, 3, 'KiB')
        );

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
            '0.00 B',
            SearchEngineMonitorServiceAbstract::formatBytes(0, 2, null, 1000)
        );
        $this->assertEquals(
            '0 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(0, 0, 'kB', 1000)
        );

        $this->assertEquals(
            '1.00 B',
            SearchEngineMonitorServiceAbstract::formatBytes(1, 2, null, 1000)
        );
        $this->assertEquals(
            '0 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(1, 0, 'kB', 1000)
        );
        $this->assertEquals(
            '0.001 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(1, 3, 'kB', 1000)
        );

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

    public function testBinaryThresholds()
    {
//        for ($b=1; $b<1280; $b++) {
//            dump($b, SearchEngineMonitorServiceAbstract::formatBytes($b, 2, null, 1024));
//        }

        $this->assertEquals(
            '31.00 B',
            SearchEngineMonitorServiceAbstract::formatBytes(31)
        );
        $this->assertEquals(
            '0.03 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(32)
        );
        $this->assertEquals(
            '0.99 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1018)
        );
        $this->assertEquals(
            '1.00 KiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1019)
        );
        $this->assertEquals(
            '1.00 GiB'/*Avoid '1024.00 MiB'*/,
            SearchEngineMonitorServiceAbstract::formatBytes(1023.995 * 1024 * 1024)
        );
        $this->assertEquals(
            '1024.00 MiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1023.995 * 1024 * 1024, 2, 'MiB')
        );
        $this->assertEquals(
            '1.000 GiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1023.995 * 1024 * 1024, 3)
        );
        $this->assertEquals(
            '1.0000 GiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1023.995 * 1024 * 1024, 4)
        );
        $this->assertEquals(
            '1.00000 GiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1023.995 * 1024 * 1024, 5)
        );
        $this->assertEquals(
            '0.999995 GiB',
            SearchEngineMonitorServiceAbstract::formatBytes(1023.995 * 1024 * 1024, 6)
        );
    }

    public function testMetricThresholds()
    {
        $this->assertEquals(
            '31.00 B',
            SearchEngineMonitorServiceAbstract::formatBytes(31, 2, null, 1000)
        );
        $this->assertEquals(
            '0.03 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(32, 2, null, 1000)
        );
        $this->assertEquals(
            '0.99 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(994, 2, null, 1000)
        );
        $this->assertEquals(
            '1.00 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(995, 2, null, 1000)
        );
        $this->assertEquals(
            '1.00 MB'/*Avoid '1000.00 kB'*/,
            SearchEngineMonitorServiceAbstract::formatBytes(999995, 2, null, 1000)
        );
        $this->assertEquals(
            '1000.00 kB',
            SearchEngineMonitorServiceAbstract::formatBytes(999995, 2, 'kB', 1000)
        );
        $this->assertEquals(
            '1.000 MB',
            SearchEngineMonitorServiceAbstract::formatBytes(999995, 3, null, 1000)
        );
        $this->assertEquals(
            '1.0000 MB',
            SearchEngineMonitorServiceAbstract::formatBytes(999995, 4, null, 1000)
        );
        $this->assertEquals(
            '1.00000 MB',
            SearchEngineMonitorServiceAbstract::formatBytes(999995, 5, null, 1000)
        );
        $this->assertEquals(
            '0.999995 MB',
            SearchEngineMonitorServiceAbstract::formatBytes(999995, 6, null, 1000)
        );
    }
}
