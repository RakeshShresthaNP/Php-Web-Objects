<?php

declare(strict_types=1);

namespace Finance\Datasets\Contracts;

/**
 * Interface for adapters that provide historical data.
 * 
 * Future improvements:
 * - Return type could be HistoricalDataCollection instead of array
 * - Parameters could use value objects: DateRange, Symbol, Interval
 * - Example: getHistoricalData(Symbol $symbol, DateRange $range, ?Interval $interval = null): HistoricalDataCollection
 */
interface HistoricalDataInterface
{
    /**
     * Fetch historical price data.
     * 
     * @param string $symbol Ticker symbol
     * @param string|null $startDate Start date in YYYY-MM-DD format (future: DateTimeInterface)
     * @param string|null $endDate End date in YYYY-MM-DD format
     * @param string $interval Data interval (1d, 1wk, 1mo) (future: Interval enum)
     * @return array Array of normalized price data (future: HistoricalDataCollection)
     */
    public function getHistoricalData(
        string $symbol,
        ?string $startDate = null,
        ?string $endDate = null,
        string $interval = '1d'
    ): array;
}