<?php

declare(strict_types=1);

namespace Finance\Datasets\Adapters;

use Finance\Datasets\Contracts\DataSourceAdapterInterface;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Base adapter class providing common functionality.
 * 
 * Future improvements:
 * - Could use a configuration object instead of array
 * - Could implement retry logic with exponential backoff
 * - Could add logging via PSR-3 LoggerInterface
 */
abstract class AbstractAdapter implements DataSourceAdapterInterface
{
    protected array $config = [];
    protected array $defaultHeaders = [
        'User-Agent' => 'PHPFinance/1.0',
    ];
    
    public function __construct(
        protected ClientInterface $httpClient,
        protected RequestFactoryInterface $requestFactory,
        protected UriFactoryInterface $uriFactory,
        protected ?CacheItemPoolInterface $cache = null,
        array $config = []
    ) {
        $this->config = array_merge($this->getDefaultConfig(), $config);
        $this->validateConfig();
    }
    
    /**
     * Get default configuration for this adapter.
     * 
     * @return array Default configuration values
     */
    protected function getDefaultConfig(): array
    {
        return [];
    }
    
    /**
     * Validate configuration.
     * 
     * @throws \InvalidArgumentException if required config is missing
     */
    protected function validateConfig(): void
    {
        foreach ($this->getRequiredConfig() as $key) {
            if (!isset($this->config[$key])) {
                throw new \InvalidArgumentException(
                    sprintf('Required configuration "%s" is missing for %s adapter', $key, $this->getName())
                );
            }
        }
    }
    
    /**
     * Get value from cache.
     * 
     * @param string $key Cache key
     * @return mixed|null Cached value or null if not found
     */
    protected function getFromCache(string $key): mixed
    {
        if (!$this->cache) {
            return null;
        }
        
        try {
            $item = $this->cache->getItem($this->getCacheKey($key));
            return $item->isHit() ? $item->get() : null;
        } catch (\Psr\Cache\InvalidArgumentException $e) {
            return null;
        }
    }
    
    /**
     * Save value to cache.
     * 
     * @param string $key Cache key
     * @param mixed $data Data to cache
     * @param int $ttl Time to live in seconds
     */
    protected function saveToCache(string $key, mixed $data, int $ttl = 3600): void
    {
        if (!$this->cache) {
            return;
        }
        
        try {
            $item = $this->cache->getItem($this->getCacheKey($key));
            $item->set($data);
            $item->expiresAfter($ttl);
            $this->cache->save($item);
        } catch (\Psr\Cache\InvalidArgumentException $e) {
            // Silently fail on cache errors
        }
    }
    
    /**
     * Generate cache key with adapter prefix.
     * 
     * @param string $key Base cache key
     * @return string Prefixed cache key
     */
    protected function getCacheKey(string $key): string
    {
        return strtolower($this->getName()) . '_' . $key;
    }
    
    /**
     * Check if adapter is available.
     * Default implementation checks for required config.
     * 
     * @return bool
     */
    public function isAvailable(): bool
    {
        try {
            $this->validateConfig();
            return true;
        } catch (\InvalidArgumentException $e) {
            return false;
        }
    }
    
    /**
     * Default normalization (can be overridden).
     * 
     * @param mixed $data Raw data
     * @return array Normalized data
     */
    public function normalizeData(mixed $data): array
    {
        // Default implementation - adapters should override
        return is_array($data) ? $data : [];
    }
}