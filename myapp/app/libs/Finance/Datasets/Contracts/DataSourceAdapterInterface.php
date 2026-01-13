<?php

declare(strict_types=1);

namespace Finance\Datasets\Contracts;

/**
 * Contract for data source adapters.
 * 
 * Implement this interface to create custom data source adapters
 * for different financial data providers.
 * 
 * Future improvements could include:
 * - DTOs (Data Transfer Objects) for type-safe data structures
 * - Value objects for dates (Carbon/DateTimeImmutable), money (Money PHP), etc.
 * - Dedicated response classes like PriceData, QuoteData, OptionChain
 * - Example: PriceData::fromArray(['date' => Carbon::parse($date), 'price' => Money::USD($price)])
 */
interface DataSourceAdapterInterface
{
    /**
     * Get the adapter name.
     * 
     * @return string Unique identifier for this adapter
     */
    public function getName(): string;
    
    /**
     * Check if the adapter is available and properly configured.
     * 
     * @return bool True if the adapter can be used
     */
    public function isAvailable(): bool;
    
    /**
     * Get supported features by this adapter.
     * 
     * @return array List of supported features (e.g., ['historical', 'realtime', 'options'])
     */
    public function getSupportedFeatures(): array;
    
    /**
     * Get configuration requirements.
     * 
     * @return array List of required configuration keys (e.g., ['api_key'])
     */
    public function getRequiredConfig(): array;
    
    /**
     * Normalize data to a common format.
     * 
     * Currently returns arrays, but future versions could return DTOs:
     * - return new PriceDataCollection($normalizedData);
     * - Each item could be a PriceData object with typed properties
     * - Automatic validation and type casting
     * 
     * Current format (array):
     * [
     *     'date' => 'YYYY-MM-DD',  // Future: DateTimeImmutable or Carbon
     *     'open' => float,          // Future: Money object with currency
     *     'high' => float,
     *     'low' => float,
     *     'close' => float,
     *     'volume' => int,
     *     'adjusted_close' => float (optional)
     * ]
     * 
     * @param mixed $data Raw data from the source
     * @return array Normalized data (future versions may return DTOs)
     */
    public function normalizeData(mixed $data): array;
}