<?php

declare(strict_types=1);

namespace Finance\Datasets\Adapters\YahooFinance;

use Finance\Datasets\Adapters\AbstractAdapter;
use Finance\Datasets\Contracts\HistoricalDataInterface;
use RuntimeException;
use InvalidArgumentException;

/**
 * Yahoo Finance v7 adapter (legacy CSV download API).
 * 
 * IMPORTANT: This API now requires authentication and may return 401 Unauthorized
 * for unauthenticated requests. Use YahooFinanceV8Adapter for public access.
 * 
 * Features:
 * - Historical price data with adjusted close
 * - CSV format (cleaner than JSON for some use cases)
 * - More precise adjusted close calculations
 * 
 * Requires:
 * - Authentication cookies/session (not implemented here)
 * - Or corporate/enterprise API access
 * 
 * Future improvements:
 * - Add authentication support (cookies, API keys)
 * - Add retry logic for 401 responses
 * - Return typed DTOs instead of arrays
 */
class YahooFinanceV7Adapter extends AbstractAdapter implements HistoricalDataInterface
{
    private const BASE_URL = 'https://query1.finance.yahoo.com/v7/finance/download/';
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'yahoo_finance_v7';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures(): array
    {
        return ['historical', 'adjusted_close', 'csv_format', 'requires_auth'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequiredConfig(): array
    {
        return []; // Could require auth credentials in the future
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'timeout' => 30,
            'verify_ssl' => true,
            'warn_auth' => true, // Warn about authentication requirements
        ];
    }
    
    /**
     * {@inheritdoc}
     */
    public function isAvailable(): bool
    {
        // Always return true, but requests may fail with 401
        return true;
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
        $cacheKey = sprintf('v7_hist_%s_%d_%d_%s', $symbol, $period1, $period2, $interval);
        if ($cached = $this->getFromCache($cacheKey)) {
            return $cached;
        }
        
        // Build request URL
        $params = [
            'period1' => $period1,
            'period2' => $period2,
            'interval' => $interval,
            'events' => 'history',
        ];
        
        $uri = $this->uriFactory->createUri(self::BASE_URL . urlencode($symbol))
            ->withQuery(http_build_query($params));
        
        try {
            $request = $this->requestFactory->createRequest('GET', (string) $uri);
            $request = $this->addHeaders($request);
            
            $response = $this->httpClient->sendRequest($request);
            
            // Check for authentication error
            if ($response->getStatusCode() === 401) {
                throw new RuntimeException(
                    'Yahoo Finance v7 API requires authentication. ' .
                    'Consider using YahooFinanceV8Adapter or provide authentication credentials. ' .
                    'Error: ' . $response->getBody()->getContents()
                );
            }
            
            $csvContent = $response->getBody()->getContents();
            $normalized = $this->normalizeData($csvContent);
            
            // Cache for 1 hour
            $this->saveToCache($cacheKey, $normalized, 3600);
            
            return $normalized;
            
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            // Check if it's a 401 Unauthorized error
            if (method_exists($e, 'getResponse') && $e->getResponse() && $e->getResponse()->getStatusCode() === 401) {
                throw new RuntimeException(
                    'Yahoo Finance v7 API requires authentication. ' .
                    'This API now requires login cookies or API keys. ' .
                    'Consider using YahooFinanceV8Adapter for public access.'
                );
            }
            
            throw new RuntimeException('Failed to fetch data from Yahoo Finance v7: ' . $e->getMessage());
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function normalizeData(mixed $data): array
    {
        if (!is_string($data)) {
            throw new InvalidArgumentException('Expected CSV string data');
        }
        
        $lines = explode("\n", $data);
        $headers = str_getcsv(array_shift($lines), escape: '\\');
        
        $normalized = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }
            
            $row = str_getcsv($line, escape: '\\');
            
            if (count($row) !== count($headers)) {
                continue; // Skip malformed rows
            }
            
            $entry = [];
            foreach ($headers as $i => $header) {
                $value = $row[$i];
                
                if ($header === 'Date') {
                    $entry['date'] = $value;
                } elseif ($value !== 'null' && $value !== '') {
                    // Map CSV headers to our standard format
                    $normalizedKey = match ($header) {
                        'Open' => 'open',
                        'High' => 'high',
                        'Low' => 'low',
                        'Close' => 'close',
                        'Adj Close' => 'adjusted_close', // v7 advantage!
                        'Volume' => 'volume',
                        default => strtolower(str_replace(' ', '_', $header))
                    };
                    
                    $entry[$normalizedKey] = floatval($value);
                } else {
                    $normalizedKey = match ($header) {
                        'Open' => 'open',
                        'High' => 'high', 
                        'Low' => 'low',
                        'Close' => 'close',
                        'Adj Close' => 'adjusted_close',
                        'Volume' => 'volume',
                        default => strtolower(str_replace(' ', '_', $header))
                    };
                    
                    $entry[$normalizedKey] = null;
                }
            }
            
            // Future: Return PriceData DTO with adjusted close
            $normalized[] = $entry;
        }
        
        return $normalized;
    }
    
    /**
     * Add required headers to request.
     * 
     * Note: For authentication, you might need to add cookies or API keys here.
     * 
     * @param \Psr\Http\Message\RequestInterface $request
     * @return \Psr\Http\Message\RequestInterface
     */
    protected function addHeaders($request)
    {
        return $request
            ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
            ->withHeader('Accept', 'text/csv,application/csv')
            ->withHeader('Accept-Language', 'en-US,en;q=0.9');
            // Future: Add authentication headers here
            // ->withHeader('Cookie', $this->config['cookies'] ?? '')
            // ->withHeader('Authorization', $this->config['api_key'] ?? '')
    }
    
    /**
     * Validate interval parameter.
     * 
     * @param string $interval
     * @throws InvalidArgumentException if interval is invalid
     */
    protected function validateInterval(string $interval): void
    {
        $validIntervals = ['1d', '5d', '1wk', '1mo', '3mo'];
        
        if (!in_array($interval, $validIntervals)) {
            throw new InvalidArgumentException(
                sprintf('Invalid interval "%s" for v7 API. Valid intervals: %s', 
                    $interval, implode(', ', $validIntervals))
            );
        }
    }
    
    /**
     * Add authentication to the adapter.
     * 
     * This method can be used to add cookies or API keys for authentication.
     * 
     * @param array $authConfig Authentication configuration
     * @return self
     */
    public function withAuth(array $authConfig): self
    {
        $this->config = array_merge($this->config, $authConfig);
        return $this;
    }
}