<?php

use AdrienDupuis\EzPlatformAdminBundle\Service\Monitor\ServerMonitorServiceAbstract;
use PHPUnit\Framework\TestCase;

class ServerMonitorServiceAbstractTest extends TestCase
{
    public function testBinaryFormatBytes()
    {
        $this->assertEquals(
            '0 B',
            ServerMonitorServiceAbstract::formatBytes(0)
        );
        $this->assertEquals(
            '0 KiB',
            ServerMonitorServiceAbstract::formatBytes(0, 0, 'KiB')
        );

        $this->assertEquals(
            '1 B',
            ServerMonitorServiceAbstract::formatBytes(1)
        );
        $this->assertEquals(
            '0 KiB',
            ServerMonitorServiceAbstract::formatBytes(1, 0, 'KiB')
        );
        $this->assertEquals(
            '0.001 KiB',
            ServerMonitorServiceAbstract::formatBytes(1, 3, 'KiB')
        );

        $this->assertEquals(
            '1.00 KiB',
            ServerMonitorServiceAbstract::formatBytes(2 ** 10)
        );
        $this->assertEquals(
            '1 KiB',
            ServerMonitorServiceAbstract::formatBytes(2 ** 10, 0)
        );

        $this->assertEquals(
            '1.00 MiB',
            ServerMonitorServiceAbstract::formatBytes(2 ** 20)
        );
        $this->assertEquals(
            '1024.00 KiB',
            ServerMonitorServiceAbstract::formatBytes(2 ** 20, 2, 'KiB')
        );

        $this->assertEquals(
            '1.50 GiB',
            ServerMonitorServiceAbstract::formatBytes(1.5 * 2 ** 30)
        );
    }

    public function testMetricFormatBytes()
    {
        $this->assertEquals(
            '0 B',
            ServerMonitorServiceAbstract::formatBytes(0, 2, null, 1000)
        );
        $this->assertEquals(
            '0 kB',
            ServerMonitorServiceAbstract::formatBytes(0, 0, 'kB', 1000)
        );

        $this->assertEquals(
            '1 B',
            ServerMonitorServiceAbstract::formatBytes(1, 2, null, 1000)
        );
        $this->assertEquals(
            '0 kB',
            ServerMonitorServiceAbstract::formatBytes(1, 0, 'kB', 1000)
        );
        $this->assertEquals(
            '0.001 kB',
            ServerMonitorServiceAbstract::formatBytes(1, 3, 'kB', 1000)
        );

        $this->assertEquals(
            '1.00 kB',
            ServerMonitorServiceAbstract::formatBytes(10 ** 3, 2, null, 1000)
        );
        $this->assertEquals(
            '1 kB',
            ServerMonitorServiceAbstract::formatBytes(10 ** 3, 0, null, 1000)
        );

        $this->assertEquals(
            '1.00 MB',
            ServerMonitorServiceAbstract::formatBytes(10 ** 6, 2, null, 1000)
        );
        $this->assertEquals(
            '1000.00 kB',
            ServerMonitorServiceAbstract::formatBytes(10 ** 6, 2, 'kB', 1000)
        );

        $this->assertEquals(
            '1.50 GB',
            ServerMonitorServiceAbstract::formatBytes(1.5 * 10 ** 9, 2, null, 1000)
        );
    }

    public function testBinaryThresholds()
    {
        $this->assertEquals(
            '1023 B',
            ServerMonitorServiceAbstract::formatBytes(1023)
        );
        $this->assertEquals(
            '1.00 KiB',
            ServerMonitorServiceAbstract::formatBytes(1024)
        );
        $this->assertEquals(
            '1023.00 KiB',
            ServerMonitorServiceAbstract::formatBytes(1023 * 1024)
        );
        $this->assertEquals(
            '1023.99 KiB',
            ServerMonitorServiceAbstract::formatBytes(1024 * 1024 - 6)
        );
        $this->assertEquals(
            '1.00 MiB',
            ServerMonitorServiceAbstract::formatBytes(1024 * 1024 - 5)
        );
        $this->assertEquals(
            '1023.00 MiB',
            ServerMonitorServiceAbstract::formatBytes(1023 * 1024 * 1024)
        );
        $this->assertEquals(
            '1023.99 MiB',
            ServerMonitorServiceAbstract::formatBytes(1024 * 1024 * 1024 - 5 * 1024 - 123)
        );
        $this->assertEquals(
            '1.00 GiB'/*Avoid '1024.00 MiB'*/,
            ServerMonitorServiceAbstract::formatBytes(1024 * 1024 * 1024 - 5 * 1024 - 122)
        );
        $this->assertEquals(
            '1023.995 MiB',
            ServerMonitorServiceAbstract::formatBytes(1024 * 1024 * 1024 - 5 * 1024 - 122, 3)
        );
    }

    public function testMetricThresholds()
    {
        $this->assertEquals(
            '999 B',
            ServerMonitorServiceAbstract::formatBytes(999, 2, null, 1000)
        );
        $this->assertEquals(
            '1.00 kB',
            ServerMonitorServiceAbstract::formatBytes(1000, 2, null, 1000)
        );
        $this->assertEquals(
            '1.00 MB'/*Avoid '1000.00 kB'*/,
            ServerMonitorServiceAbstract::formatBytes(999995, 2, null, 1000)
        );
        $this->assertEquals(
            '1000.00 kB',
            ServerMonitorServiceAbstract::formatBytes(999995, 2, 'kB', 1000)
        );
        $this->assertEquals(
            '999.995 kB',
            ServerMonitorServiceAbstract::formatBytes(999995, 3, null, 1000)
        );
        $this->assertEquals(
            '999.9950 kB',
            ServerMonitorServiceAbstract::formatBytes(999995, 4, null, 1000)
        );
    }
}
