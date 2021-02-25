<?php

use AdrienDupuis\EzPlatformAdminBundle\Service\IntegrityService;
use PHPUnit\Framework\TestCase;

class IntegrityServiceTest extends TestCase
{
    public function testBinaryParseBytes()
    {
        $this->assertEquals(
            0,
            IntegrityService::parseBytes('0 B')
        );
        $this->assertEquals(
            0,
            IntegrityService::parseBytes('0KiB')
        );

        $this->assertEquals(
            1024,
            IntegrityService::parseBytes('1K')
        );
        $this->assertEquals(
            1024,
            IntegrityService::parseBytes('1 KiB')
        );
        $this->assertEquals(
            1,
            IntegrityService::parseBytes('0.001 KiB')
        );
        $this->assertEquals(
            2 * 1024 * 1024,
            IntegrityService::parseBytes('2M')
        );
        $this->assertEquals(
            2 * 1024 * 1024,
            IntegrityService::parseBytes('2MiB')
        );
    }

    public function testMetricparseBytes()
    {
        $this->assertEquals(
            2 * 1000 * 1000,
            IntegrityService::parseBytes('2 MB')
        );
    }
}
