<?php

declare(strict_types=1);

namespace Finance\Datasets\Adapters\Fred;

use Finance\Datasets\Adapters\AbstractAdapter;
use Adbar\Dot;
use RuntimeException;
use InvalidArgumentException;

/**
 * FRED (Federal Reserve Economic Data) adapter.
 * 
 * Provides access to thousands of economic time series from the St. Louis Fed.
 * 
 * Features:
 * - Economic indicators (GDP, CPI, unemployment)
 * - Interest rates (Treasury yields, LIBOR, Fed Funds)
 * - Exchange rates
 * - Commodity prices
 * 
 * Future improvements:
 * - Return EconomicData DTOs with metadata
 * - Add series search functionality
 * - Add category browsing
 * - Support for real-time updates via FRED's release calendar
 */
class FredAdapter extends AbstractAdapter
{
    private const BASE_URL = 'https://api.stlouisfed.org/fred/';
    
    /**
     * Common FRED series IDs for reference.
     */
    public const SERIES = [
        // Interest Rates
        'DGS10' => '10-Year Treasury Constant Maturity Rate',
        'DGS2' => '2-Year Treasury Constant Maturity Rate',
        'DGS30' => '30-Year Treasury Constant Maturity Rate',
        'FEDFUNDS' => 'Effective Federal Funds Rate',
        'SOFR' => 'Secured Overnight Financing Rate',
        
        // Economic Indicators
        'GDP' => 'Gross Domestic Product',
        'CPIAUCSL' => 'Consumer Price Index for All Urban Consumers',
        'UNRATE' => 'Unemployment Rate',
        'PAYEMS' => 'All Employees: Total Nonfarm Payrolls',
        'HOUST' => 'Housing Starts',
        
        // Market Indices
        'DEXUSEU' => 'U.S. / Euro Foreign Exchange Rate',
        'DEXJPUS' => 'Japan / U.S. Foreign Exchange Rate',
        'DCOILWTICO' => 'Crude Oil Prices: WTI',
        'GOLDAMGBD228NLBM' => 'Gold Fixing Price',
        
        // Volatility
        'VIXCLS' => 'CBOE Volatility Index: VIX',
    ];
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'fred';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures(): array
    {
        return ['economic_data', 'time_series', 'metadata'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequiredConfig(): array
    {
        return ['api_key'];
    }
    
    /**
     * {@inheritdoc}
     */
    protected function getDefaultConfig(): array
    {
        return [
            'api_key' => getenv('FRED_API_KEY') ?: null,
            'file_type' => 'json',
        ];
    }
    
    /**
     * Get series observations (time series data).
     * 
     * @param string $seriesId FRED series ID (e.g., 'DGS10')
     * @param string|null $startDate Start date in YYYY-MM-DD format
     * @param string|null $endDate End date in YYYY-MM-DD format
     * @param array $options Additional options (frequency, aggregation_method, etc.)
     * @return array Time series data
     * @throws RuntimeException if request fails
     */
    public function getSeries(
        string $seriesId,
        ?string $startDate = null,
        ?string $endDate = null,
        array $options = []
    ): array {
        // Check cache
        $cacheKey = sprintf('series_%s_%s_%s_%s', 
            $seriesId, 
            $startDate ?? 'null', 
            $endDate ?? 'null',
            md5(serialize($options))
        );
        
        if ($cached = $this->getFromCache($cacheKey)) {
            return $cached;
        }
        
        $params = array_merge([
            'series_id' => $seriesId,
            'api_key' => $this->config['api_key'],
            'file_type' => $this->config['file_type'],
        ], $options);
        
        if ($startDate) {
            $params['observation_start'] = $startDate;
        }
        
        if ($endDate) {
            $params['observation_end'] = $endDate;
        }
        
        $uri = $this->uriFactory->createUri(self::BASE_URL . 'series/observations')
            ->withQuery(http_build_query($params));
        
        try {
            $request = $this->requestFactory->createRequest('GET', (string) $uri);
            $request = $request->withHeader('User-Agent', $this->defaultHeaders['User-Agent']);
            
            $response = $this->httpClient->sendRequest($request);
            $content = $response->getBody()->getContents();
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
            }
            
            $normalized = $this->normalizeData($data);
            
            // Cache for 24 hours (FRED data updates daily at most)
            $this->saveToCache($cacheKey, $normalized, 86400);
            
            return $normalized;
            
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to fetch FRED data: ' . $e->getMessage());
        }
    }
    
    /**
     * Get series metadata.
     * 
     * @param string $seriesId FRED series ID
     * @return array Series metadata
     */
    public function getSeriesInfo(string $seriesId): array
    {
        $cacheKey = 'info_' . $seriesId;
        
        if ($cached = $this->getFromCache($cacheKey)) {
            return $cached;
        }
        
        $params = [
            'series_id' => $seriesId,
            'api_key' => $this->config['api_key'],
            'file_type' => $this->config['file_type'],
        ];
        
        $uri = $this->uriFactory->createUri(self::BASE_URL . 'series')
            ->withQuery(http_build_query($params));
        
        try {
            $request = $this->requestFactory->createRequest('GET', (string) $uri);
            $request = $request->withHeader('User-Agent', $this->defaultHeaders['User-Agent']);
            
            $response = $this->httpClient->sendRequest($request);
            $content = $response->getBody()->getContents();
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
            }
            
            $dot = new Dot($data);
            $series = $dot->get('seriess.0', []);
            
            if (empty($series)) {
                throw new RuntimeException('Series not found: ' . $seriesId);
            }
            
            // Cache for 7 days (metadata rarely changes)
            $this->saveToCache($cacheKey, $series, 604800);
            
            return $series;
            
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to fetch series info: ' . $e->getMessage());
        }
    }
    
    /**
     * Search for series.
     * 
     * @param string $searchText Search query
     * @param array $options Additional search options
     * @return array Search results
     */
    public function searchSeries(string $searchText, array $options = []): array
    {
        $params = array_merge([
            'search_text' => $searchText,
            'api_key' => $this->config['api_key'],
            'file_type' => $this->config['file_type'],
        ], $options);
        
        $uri = $this->uriFactory->createUri(self::BASE_URL . 'series/search')
            ->withQuery(http_build_query($params));
        
        try {
            $request = $this->requestFactory->createRequest('GET', (string) $uri);
            $request = $request->withHeader('User-Agent', $this->defaultHeaders['User-Agent']);
            
            $response = $this->httpClient->sendRequest($request);
            $content = $response->getBody()->getContents();
            
            $data = json_decode($content, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
            }
            
            $dot = new Dot($data);
            return $dot->get('seriess', []);
            
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to search series: ' . $e->getMessage());
        }
    }
    
    /**
     * {@inheritdoc}
     */
    public function normalizeData(mixed $data): array
    {
        if (!is_array($data)) {
            return [];
        }
        
        $dot = new Dot($data);
        $observations = $dot->get('observations', []);
        
        if (empty($observations)) {
            return [];
        }
        
        $normalized = [
            'series_id' => $dot->get('series_id'),
            'title' => $dot->get('title'),
            'units' => $dot->get('units'),
            'frequency' => $dot->get('frequency'),
            'data' => [],
        ];
        
        foreach ($observations as $obs) {
            // Skip missing values
            if ($obs['value'] === '.' || $obs['value'] === '') {
                continue;
            }
            
            // Future: Return EconomicDataPoint DTO
            $normalized['data'][] = [
                'date' => $obs['date'],
                'value' => floatval($obs['value']),
                // Future: Add metadata like realtime_start, realtime_end
            ];
        }
        
        return $normalized;
    }
    
    /**
     * Get common series (convenience method).
     * 
     * @param string $key Series key from SERIES constant
     * @param string|null $startDate Start date
     * @param string|null $endDate End date
     * @return array Series data
     */
    public function getCommonSeries(string $key, ?string $startDate = null, ?string $endDate = null): array
    {
        if (!isset(self::SERIES[$key])) {
            throw new InvalidArgumentException(
                sprintf('Unknown series key "%s". Available: %s', $key, implode(', ', array_keys(self::SERIES)))
            );
        }
        
        return $this->getSeries($key, $startDate, $endDate);
    }
}