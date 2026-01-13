<?php

declare(strict_types=1);

namespace Finance\Datasets\Adapters\YahooFinance;

use Finance\Datasets\Adapters\AbstractAdapter;
use Finance\Datasets\Contracts\HistoricalDataInterface;
use Finance\Datasets\Contracts\RealtimeDataInterface;
use Adbar\Dot;
use RuntimeException;
use InvalidArgumentException;

/**
 * Yahoo Finance v8 adapter using chart API.
 * 
 * Features:
 * - Historical price data (OHLCV)
 * - Real-time quotes
 * - Multiple intervals (1d, 1wk, 1mo)
 * - No authentication required
 * - JSON format responses
 * 
 * Note: Does not provide adjusted close prices (use v7 adapter if needed)
 * 
 * Future improvements:
 * - Add options chain support
 * - Add fundamentals data
 * - Add news feed
 * - Return typed DTOs instead of arrays
 */
class YahooFinanceV8Adapter extends AbstractAdapter implements HistoricalDataInterface, RealtimeDataInterface
{
    private const BASE_URL = 'https://query1.finance.yahoo.com/v8/finance/chart/';
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'yahoo_finance_v8';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures(): array
    {
        return ['historical', 'realtime', 'intervals'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequiredConfig(): array
    {
        return []; // Yahoo Finance doesn't require API keys
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
            'verify_ssl' => true,
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getHistoricalData(
        string $symbol,
        ?string $startDate = null,
        ?string $endDate = null,
        string $interval = '1d'
    ): array {
        $this->validateInterval($interval);
        
        $period1 = $startDate ? strtotime($startDate) : strtotime('-1 year');
        $period2 = $endDate ? strtotime($endDate) : time();
        
        if ($period1 === false || $period2 === false) {
            throw new InvalidArgumentException('Invalid date format provided');
        }
        
        if ($period1 >= $period2) {
            throw new InvalidArgumentException('Start date must be before end date');
        }
        
        // Check cache
        $cacheKey = sprintf('hist_%s_%d_%d_%s', $symbol, $period1, $period2, $interval);
        if ($cached = $this->getFromCache($cacheKey)) {
            return $cached;
        }
        
        // Build request
        $params = [
            'period1' => $period1,
            'period2' => $period2,
            'interval' => $interval,
            'includePrePost' => 'false',
            'events' => 'div|split|earn',
        ];
        
        $uri = $this->uriFactory->createUri(self::BASE_URL . urlencode($symbol))
            ->withQuery(http_build_query($params));
        
        try {
            $request = $this->requestFactory->createRequest('GET', (string) $uri);
            $request = $this->addHeaders($request);
            
            $response = $this->httpClient->sendRequest($request);
            $content = $response->getBody()->getContents();
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
            }
            
            $normalized = $this->normalizeData($data);
            
            // Cache for 1 hour
            $this->saveToCache($cacheKey, $normalized, 3600);
            
            return $normalized;
            
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to fetch data from Yahoo Finance: ' . $e->getMessage());
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getQuote(string $symbol): array
    {
        // Check cache (shorter TTL for quotes)
        $cacheKey = 'quote_' . $symbol;
        if ($cached = $this->getFromCache($cacheKey)) {
            return $cached;
        }
        
        $uri = $this->uriFactory->createUri(self::BASE_URL . urlencode($symbol));
        
        try {
            $request = $this->requestFactory->createRequest('GET', (string) $uri);
            $request = $this->addHeaders($request);
            
            $response = $this->httpClient->sendRequest($request);
            $content = $response->getBody()->getContents();
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
            }
            
            $quote = $this->normalizeQuote($data);
            
            // Cache for 60 seconds
            $this->saveToCache($cacheKey, $quote, 60);
            
            return $quote;
            
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to fetch quote from Yahoo Finance: ' . $e->getMessage());
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function getQuotes(array $symbols): array
    {
        $quotes = [];
        
        // Yahoo v8 doesn't support batch quotes, so we fetch individually
        // Future: Could use parallel requests
        foreach ($symbols as $symbol) {
            try {
                $quotes[$symbol] = $this->getQuote($symbol);
            } catch (RuntimeException $e) {
                $quotes[$symbol] = ['error' => $e->getMessage()];
            }
        }
        
        return $quotes;
    }
    
    /**
     * {@inheritdoc}
     */
    public function normalizeData(mixed $data): array
    {
        $dot = new Dot($data);
        
        $timestamps = $dot->get('chart.result.0.timestamp', []);
        $quoteData = $dot->get('chart.result.0.indicators.quote.0', []);
        
        if (empty($timestamps) || empty($quoteData)) {
            throw new RuntimeException('Missing required data in Yahoo Finance response');
        }
        
        // Wrap quote data in Dot object for efficient nested access
        $quote = new Dot($quoteData);
        
        $normalized = [];
        
        for ($i = 0; $i < count($timestamps); $i++) {
            // Future: Return PriceData DTO instead of array
            $normalized[] = [
                'date' => date('Y-m-d', $timestamps[$i]),  // Future: DateTimeImmutable
                'open' => $quote->get("open.$i"),          // Future: Money object
                'high' => $quote->get("high.$i"),
                'low' => $quote->get("low.$i"),
                'close' => $quote->get("close.$i"),
                'volume' => $quote->get("volume.$i"),
                // Note: v8 API doesn't provide adjusted close directly
            ];
        }
        
        return $normalized;
    }
    
    /**
     * Normalize quote data.
     * 
     * @param array $data Raw quote data
     * @return array Normalized quote
     */
    protected function normalizeQuote(array $data): array
    {
        $dot = new Dot($data);
        $metaData = $dot->get('chart.result.0.meta', []);
        
        if (empty($metaData)) {
            throw new RuntimeException('Invalid quote response from Yahoo Finance');
        }
        
        // Wrap meta data in Dot object for efficient nested access
        $meta = new Dot($metaData);
        
        // Future: Return Quote DTO instead of array
        return [
            'symbol' => $meta->get('symbol'),
            'price' => $meta->get('regularMarketPrice'),
            'timestamp' => $meta->get('regularMarketTime'),
            'datetime' => date('Y-m-d H:i:s', $meta->get('regularMarketTime', 0)),
            'high' => $meta->get('regularMarketDayHigh'),
            'low' => $meta->get('regularMarketDayLow'),
            'volume' => $meta->get('regularMarketVolume'),
            'previous_close' => $meta->get('previousClose', $meta->get('chartPreviousClose')),
            'change' => null, // Calculate if needed
            'change_percent' => null, // Calculate if needed
            'currency' => $meta->get('currency'),
            'exchange' => $meta->get('exchangeName'),
        ];
    }
    
    /**
     * Add required headers to request.
     * 
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function addHeaders($request)
    {
        return $request
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->withHeader('Accept', 'application/json')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9')
            ->withHeader('Cache-Control', 'no-cache')
            ->withHeader('Pragma', 'no-cache');
    }
    
    /**
     * Validate interval parameter.
     * 
     * @param string $interval
     * @throws InvalidArgumentException if interval is invalid
     */
    protected function validateInterval(string $interval): void
    {
        $validIntervals = ['1m', '2m', '5m', '15m', '30m', '60m', '90m', '1h', '1d', '5d', '1wk', '1mo', '3mo'];
        
        if (!in_array($interval, $validIntervals)) {
            throw new InvalidArgumentException(
                sprintf('Invalid interval "%s". Valid intervals: %s', $interval, implode(', ', $validIntervals))
            );
        }
    }
}