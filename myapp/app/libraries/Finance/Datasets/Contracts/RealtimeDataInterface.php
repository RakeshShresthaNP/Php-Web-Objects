<?php

declare(strict_types=1);

namespace Finance\Datasets\Contracts;

/**
 * Interface for adapters that provide real-time quote data.
 * 
 * Future improvements:
 * - Return type could be Quote DTO with typed properties
 * - Symbol could be a value object ensuring valid format
 * - Example: getQuote(Symbol $symbol): Quote
 */
interface RealtimeDataInterface
{
    /**
     * Get real-time quote for a symbol.
     * 
     * @param string $symbol Ticker symbol (future: Symbol value object)
     * @return array Current quote data (future: Quote DTO)
     */
    public function getQuote(string $symbol): array;
    
    /**
     * Get real-time quotes for multiple symbols.
     * 
     * @param array $symbols Array of ticker symbols
     * @return array Array of quote data indexed by symbol (future: QuoteCollection)
     */
    public function getQuotes(array $symbols): array;
}