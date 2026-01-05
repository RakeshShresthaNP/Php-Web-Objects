<?php

declare(strict_types=1);

namespace Finance\Datasets;

use Finance\Datasets\Contracts\DataSourceAdapterInterface;
use Finance\Datasets\Adapters\YahooFinance\YahooFinanceV7Adapter;
use Finance\Datasets\Adapters\YahooFinance\YahooFinanceV8Adapter;
use Finance\Datasets\Adapters\FamaFrench\FamaFrenchAdapter;
use Finance\Datasets\Adapters\Fred\FredAdapter;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Cache\CacheItemPoolInterface;
use InvalidArgumentException;
use RuntimeException;

/**
 * Data source manager (facade) for financial data adapters.
 * 
 * Manages multiple data source adapters and provides a unified interface
 * for accessing different financial data providers.
 * 
 * Future improvements:
 * - Lazy loading of adapters
 * - Adapter discovery via service providers
 * - Parallel data fetching from multiple sources
 * - Data merging from multiple sources
 * - Fallback adapters when primary fails
 */
class DataManager
{
    /**
     * @var array<string, DataSourceAdapterInterface> Registered adapters
     */
    private array $adapters = [];
    
    /**
     * @var array Default adapter configurations
     */
    private array $defaultConfigs = [];
    
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private UriFactoryInterface $uriFactory,
        private ?CacheItemPoolInterface $cache = null
    ) {
        $this->registerDefaultAdapters();
    }
    
    /**
     * Register default adapters.
     */
    protected function registerDefaultAdapters(): void
    {
        // Register Yahoo Finance v8 (default, no auth required)
        $this->registerAdapter(
            'yahoo',
            new YahooFinanceV8Adapter(
                $this->httpClient,
                $this->requestFactory,
                $this->uriFactory,
                $this->cache
            )
        );
        
        // Register Yahoo Finance v8 explicitly
        $this->registerAdapter(
            'yahoo_v8',
            new YahooFinanceV8Adapter(
                $this->httpClient,
                $this->requestFactory,
                $this->uriFactory,
                $this->cache
            )
        );
        
        // Register Yahoo Finance v7 (legacy, requires auth)
        $this->registerAdapter(
            'yahoo_v7',
            new YahooFinanceV7Adapter(
                $this->httpClient,
                $this->requestFactory,
                $this->uriFactory,
                $this->cache
            )
        );
        
        // Register Fama-French
        $this->registerAdapter(
            'famafrench',
            new FamaFrenchAdapter(
                $this->httpClient,
                $this->requestFactory,
                $this->uriFactory,
                $this->cache
            )
        );
        
        // Note: FRED adapter requires API key, so we don't register it by default
        // Users must call registerFredAdapter() with their API key
    }
    
    /**
     * Register a data source adapter.
     * 
     * @param string $name Adapter name
     * @param DataSourceAdapterInterface $adapter Adapter instance
     * @return self
     */
    public function registerAdapter(string $name, DataSourceAdapterInterface $adapter): self
    {
        $this->adapters[$name] = $adapter;
        return $this;
    }
    
    /**
     * Register FRED adapter with API key.
     * 
     * @param string $apiKey FRED API key
     * @return self
     */
    public function registerFredAdapter(string $apiKey): self
    {
        $this->registerAdapter(
            'fred',
            new FredAdapter(
                $this->httpClient,
                $this->requestFactory,
                $this->uriFactory,
                $this->cache,
                ['api_key' => $apiKey]
            )
        );
        
        return $this;
    }
    
    /**
     * Get an adapter by name.
     * 
     * @param string $name Adapter name
     * @return DataSourceAdapterInterface
     * @throws InvalidArgumentException if adapter not found
     */
    public function getAdapter(string $name): DataSourceAdapterInterface
    {
        if (!isset($this->adapters[$name])) {
            throw new InvalidArgumentException(
                sprintf('Adapter "%s" not found. Available: %s', $name, implode(', ', array_keys($this->adapters)))
            );
        }
        
        return $this->adapters[$name];
    }
    
    /**
     * Check if an adapter is registered.
     * 
     * @param string $name Adapter name
     * @return bool
     */
    public function hasAdapter(string $name): bool
    {
        return isset($this->adapters[$name]);
    }
    
    /**
     * Get all registered adapters.
     * 
     * @return array<string, DataSourceAdapterInterface>
     */
    public function getAdapters(): array
    {
        return $this->adapters;
    }
    
    /**
     * Get Yahoo Finance adapter (defaults to v8).
     * 
     * @return YahooFinanceV8Adapter
     */
    public function yahoo(): YahooFinanceV8Adapter
    {
        return $this->getAdapter('yahoo');
    }
    
    /**
     * Get Yahoo Finance v8 adapter (explicit).
     * 
     * @return YahooFinanceV8Adapter
     */
    public function yahooV8(): YahooFinanceV8Adapter
    {
        return $this->getAdapter('yahoo_v8');
    }
    
    /**
     * Get Yahoo Finance v7 adapter (legacy).
     * 
     * @return YahooFinanceV7Adapter
     */
    public function yahooV7(): YahooFinanceV7Adapter
    {
        return $this->getAdapter('yahoo_v7');
    }
    
    /**
     * Get Fama-French adapter (convenience method).
     * 
     * @return FamaFrenchAdapter
     */
    public function famaFrench(): FamaFrenchAdapter
    {
        return $this->getAdapter('famafrench');
    }
    
    /**
     * Get FRED adapter (convenience method).
     * 
     * @return FredAdapter
     * @throws RuntimeException if FRED adapter not registered
     */
    public function fred(): FredAdapter
    {
        if (!$this->hasAdapter('fred')) {
            throw new RuntimeException(
                'FRED adapter not registered. Call registerFredAdapter() with your API key first.'
            );
        }
        
        return $this->getAdapter('fred');
    }
    
    /**
     * Create a custom adapter instance.
     * 
     * @param string $className Fully qualified class name
     * @param array $config Adapter configuration
     * @return DataSourceAdapterInterface
     */
    public function createAdapter(string $className, array $config = []): DataSourceAdapterInterface
    {
        if (!class_exists($className)) {
            throw new InvalidArgumentException('Adapter class not found: ' . $className);
        }
        
        if (!is_subclass_of($className, DataSourceAdapterInterface::class)) {
            throw new InvalidArgumentException(
                'Adapter must implement ' . DataSourceAdapterInterface::class
            );
        }
        
        return new $className(
            $this->httpClient,
            $this->requestFactory,
            $this->uriFactory,
            $this->cache,
            $config
        );
    }
    
    /**
     * Get adapter information.
     * 
     * @return array Information about all registered adapters
     */
    public function getAdapterInfo(): array
    {
        $info = [];
        
        foreach ($this->adapters as $name => $adapter) {
            $info[$name] = [
                'name' => $adapter->getName(),
                'available' => $adapter->isAvailable(),
                'features' => $adapter->getSupportedFeatures(),
                'required_config' => $adapter->getRequiredConfig(),
            ];
        }
        
        return $info;
    }
}