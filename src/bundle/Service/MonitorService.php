<?php

namespace AdrienDupuis\EzPlatformAdminBundle\Service;

use EzSystems\EzPlatformSolrSearchEngine\Gateway\Endpoint;
use EzSystems\EzPlatformSolrSearchEngine\Gateway\EndpointRegistry;
use EzSystems\EzPlatformSolrSearchEngine\Gateway\EndpointResolver;
use EzSystems\EzPlatformSolrSearchEngine\Gateway\HttpClient;
use EzSystems\EzPlatformSolrSearchEngine\Gateway\Message;

class MonitorService
{
    /** @var EndpointResolver */
    private $solrEndpointResolver;

    /** @var EndpointRegistry */
    private $solrEndpointRegistry;

    /** @var HttpClient */
    private $solrHttpClient;

    public function __construct(EndpointResolver $solrEndpointResolver, EndpointRegistry $solrEndpointRegistry, HttpClient $solrHttpClient)
    {
        $this->solrEndpointResolver = $solrEndpointResolver;
        $this->solrEndpointRegistry = $solrEndpointRegistry;
        $this->solrHttpClient = $solrHttpClient;
    }

    public static function formatBytes(float $bytes, int $precision = 2, string $unit = null)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        if ($unit) {
            $pow = array_search(strtoupper($unit), $units);
            if (false === $pow) {
                throw new \InvalidArgumentException("Unit '$unit' is invalid; available units: ".implode(', ', $units));
            }
        } else {
            $pow = min(floor(log($bytes) / log(1024)), count($units) - 1);
        }

        return round($bytes / pow(1024, $pow), $precision).' '.$units[$pow];
    }

    public function pingSolrEndpoints(): array
    {
        // https://lucene.apache.org/solr/guide/7_7/ping.html#ping-api-examples
        $path = '/admin/ping';
        $message = new Message([], 'wt=json');
        $replies = [];
        foreach ($this->solrEndpointResolver->getEndpoints() as $endpointName) {
            $endpoint = $this->solrEndpointRegistry->getEndpoint($endpointName);
            $replies[$endpointName] = json_decode($this->solrHttpClient->request('GET', $endpoint, $path, $message)->body, true);
        }

        return $replies;
    }

    public function getSolrJvmOsMetrics(): array
    {
        // https://lucene.apache.org/solr/guide/7_7/metrics-reporting.html#metrics-api
        $metrics = [];
        $path = '/metrics';
        $message = new Message([], 'wt=json&group=jvm&prefix=os');
        foreach ($this->solrEndpointResolver->getEndpoints() as $endpointName) {
            $endpoint = $this->solrEndpointRegistry->getEndpoint($endpointName);
            $adminEndpoint = new Endpoint([
                'scheme' => $endpoint->scheme,
                'user' => $endpoint->user,
                'pass' => $endpoint->pass,
                'host' => $endpoint->host,
                'port' => $endpoint->port,
                'path' => $endpoint->path,
                'core' => 'admin',
            ]);
            $identifier = str_replace('/admin', '', $adminEndpoint->getIdentifier());
            if (!array_key_exists($identifier, $metrics)) {
                $jvm = json_decode($this->solrHttpClient->request('GET', $adminEndpoint, $path, $message)->body, true)['metrics']['solr.jvm'];
                $metrics[$identifier] = [
                    'free_physical_memory' => (int) $jvm['os.freePhysicalMemorySize'],
                    'total_physical_memory' => (int) $jvm['os.totalPhysicalMemorySize'],
                    'used_physical_memory' => $jvm['os.totalPhysicalMemorySize'] - $jvm['os.freePhysicalMemorySize'],
                    'free_physical_memory_human_readable' => self::formatBytes($jvm['os.freePhysicalMemorySize']),
                    'total_physical_memory_human_readable' => self::formatBytes($jvm['os.totalPhysicalMemorySize']),
                    'used_physical_memory_human_readable' => self::formatBytes($jvm['os.totalPhysicalMemorySize'] - $jvm['os.freePhysicalMemorySize']),
                    'free_swap_space' => (int) $jvm['os.freeSwapSpaceSize'],
                    'total_swap_space' => (int) $jvm['os.totalSwapSpaceSize'],
                    'used_swap_space' => $jvm['os.totalSwapSpaceSize'] - $jvm['os.freeSwapSpaceSize'],
                    'free_swap_space_human_readable' => self::formatBytes($jvm['os.freeSwapSpaceSize']),
                    'total_swap_space_human_readable' => self::formatBytes($jvm['os.totalSwapSpaceSize']),
                    'used_swap_space_human_readable' => self::formatBytes($jvm['os.totalSwapSpaceSize'] - $jvm['os.freeSwapSpaceSize']),
                ];
            }
        }

        return $metrics;
    }
}
