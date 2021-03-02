<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service\Monitor;

use Ibexa\Platform\ElasticSearchEngine\ElasticSearch\Client\ClientFactoryInterface;

class ElasticsearchMonitorService extends ServerMonitorServiceAbstract
{
    /** @var \Elasticsearch\Client */
    private $elasticsearchClient;

    public function __construct(
        ClientFactoryInterface $elasticSearchClientFactory
    ) {
        $this->elasticsearchClient = $elasticSearchClientFactory->create();
    }

    public function ping(): bool
    {
        return $this->elasticsearchClient->ping();
    }

    public function getMetrics(): array
    {
        // https://www.elastic.co/guide/en/elasticsearch/reference/current/cluster-nodes-stats.html
        $metrics = [];
        $stats = $this->elasticsearchClient->nodes()->stats([
            'metric' => 'os',
        ]);
        foreach ($stats['nodes'] as $node => $nodeStats) {
            $metrics[$node] = [
                'free_physical_memory' => (int) $nodeStats['os']['mem']['free_in_bytes'],
                'total_physical_memory' => (int) $nodeStats['os']['mem']['total_in_bytes'],
                'used_physical_memory' => (int) $nodeStats['os']['mem']['used_in_bytes'],
                'free_physical_memory_human' => self::formatBytes($nodeStats['os']['mem']['free_in_bytes']),
                'total_physical_memory_human' => self::formatBytes($nodeStats['os']['mem']['total_in_bytes']),
                'used_physical_memory_human' => self::formatBytes($nodeStats['os']['mem']['used_in_bytes']),
                'free_physical_memory_percent' => self::formatPercent($nodeStats['os']['mem']['free_in_bytes']/$nodeStats['os']['mem']['total_in_bytes']),
                'used_physical_memory_percent' => self::formatPercent($nodeStats['os']['mem']['used_in_bytes']/$nodeStats['os']['mem']['total_in_bytes']),
                'free_swap_space' => (int) $nodeStats['os']['swap']['free_in_bytes'],
                'total_swap_space' => (int) $nodeStats['os']['swap']['total_in_bytes'],
                'used_swap_space' => (int) $nodeStats['os']['swap']['used_in_bytes'],
                'free_swap_space_human' => self::formatBytes($nodeStats['os']['swap']['free_in_bytes']),
                'total_swap_space_human' => self::formatBytes($nodeStats['os']['swap']['total_in_bytes']),
                'used_swap_space_human' => self::formatBytes($nodeStats['os']['swap']['used_in_bytes']),
                'free_swap_space_percent' => self::formatBytes($nodeStats['os']['swap']['free_in_bytes']/$nodeStats['os']['swap']['total_in_bytes']),
                'used_swap_space_percent' => self::formatBytes($nodeStats['os']['swap']['used_in_bytes']/$nodeStats['os']['swap']['total_in_bytes']),
            ];
        }

        return $metrics;
    }
}
