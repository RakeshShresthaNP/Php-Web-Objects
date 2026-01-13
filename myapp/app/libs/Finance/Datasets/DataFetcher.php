<?php

declare(strict_types=1);

namespace Finance\Datasets;

use InvalidArgumentException;
use RuntimeException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Message\RequestFactoryInterface;
use Psr\Http\Message\UriFactoryInterface;
use Psr\Http\Message\UriInterface;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\InvalidArgumentException as CacheInvalidArgumentException;
use Adbar\Dot;

/**
 * Financial data fetcher for academic and market data sources.
 * 
 * This class provides a unified interface for fetching financial data from
 * various sources commonly used in quantitative finance research and analysis:
 * 
 * - Fama-French research data (factor models, portfolio returns)
 * - FRED (Federal Reserve Economic Data)
 * - Yahoo Finance (stock prices and historical data)
 * - Robert Shiller's market data (CAPE ratio, long-term market data)
 * 
 * Features:
 * - PSR-18 HTTP client compatibility for flexible HTTP implementations
 * - PSR-6 cache support for reducing API calls and improving performance
 * - Automatic data parsing and normalization
 * - Error handling and retry logic
 * 
 * All methods return parsed, structured data ready for analysis.
 */
class DataFetcher
{
    private array $defaultHeaders;
    
    /**
     * Constructs a DataFetcher instance.
     * 
     * @param ClientInterface $httpClient PSR-18 compatible HTTP client
     * @param RequestFactoryInterface $requestFactory PSR-17 request factory
     * @param UriFactoryInterface $uriFactory PSR-17 URI factory
     * @param CacheItemPoolInterface|null $cache Optional PSR-6 cache pool for caching responses
     * @param array $defaultHeaders Default HTTP headers to include in requests
     */
    public function __construct(
        private ClientInterface $httpClient,
        private RequestFactoryInterface $requestFactory,
        private UriFactoryInterface $uriFactory,
        private ?CacheItemPoolInterface $cache = null,
        array $defaultHeaders = []
    ) {
        $this->defaultHeaders = array_merge([
            'User-Agent' => 'PHPFinance/1.0',
        ], $defaultHeaders);
    }
    
    /**
     * Fetches Fama-French research data.
     * 
     * Retrieves factor data and portfolio returns from Kenneth French's
     * data library. These datasets are essential for asset pricing models,
     * risk analysis, and academic research.
     * 
     * Available datasets include:
     * - 3-factor model (Market, SMB, HML)
     * - 5-factor model (Market, SMB, HML, RMW, CMA)
     * - Momentum factor
     * - Portfolio returns sorted by size and value
     * 
     * @param string $dataset Dataset identifier (e.g., 'F-F_Research_Data_Factors')
     * @return array Parsed data with date and factor values
     * @throws InvalidArgumentException if dataset name is invalid
     * @throws RuntimeException if download or parsing fails
     */
    public function getFamaFrench(string $dataset = 'F-F_Research_Data_Factors'): array
    {
        $validDatasets = [
            'F-F_Research_Data_Factors',
            'F-F_Research_Data_Factors_daily',
            'F-F_Research_Data_5_Factors_2x3',
            'F-F_Research_Data_5_Factors_2x3_daily',
            'F-F_Momentum_Factor',
            'F-F_Momentum_Factor_daily',
            '6_Portfolios_2x3',
            '25_Portfolios_5x5',
            '100_Portfolios_10x10',
        ];
        
        if (!in_array($dataset, $validDatasets)) {
            throw new InvalidArgumentException('Invalid Fama-French dataset name');
        }
        
        $uri = $this->uriFactory->createUri('http://mba.tuck.dartmouth.edu/pages/faculty/ken.french/ftp/')
            ->withPath('/pages/faculty/ken.french/ftp/' . $dataset . '_CSV.zip');
        
        $url = (string) $uri;
        
        $cacheKey = 'famafrench_' . md5($dataset);
        
        if ($this->cache && $cachedData = $this->getFromCache($cacheKey)) {
            return $cachedData;
        }
        
        $zipContent = $this->downloadFile($url);
        $csvContent = $this->extractZipContent($zipContent);
        $data = $this->parseFamaFrenchCSV($csvContent);
        
        if ($this->cache) {
            $this->saveToCache($cacheKey, $data, 86400); // 24 hours
        }
        
        return $data;
    }
    
    /**
     * Fetches economic data from FRED (Federal Reserve Economic Data).
     * 
     * Provides access to thousands of economic time series including:
     * - Interest rates (DGS10, FEDFUNDS, etc.)
     * - Economic indicators (GDP, CPI, unemployment)
     * - Market indices (DEXUSEU, DJIA)
     * - Commodity prices
     * 
     * Requires FRED API key (free from https://fred.stlouisfed.org/docs/api/)
     * 
     * @param string $series FRED series ID (e.g., 'DGS10' for 10-year Treasury)
     * @param string|null $startDate Start date in YYYY-MM-DD format
     * @param string|null $endDate End date in YYYY-MM-DD format
     * @param string|null $apiKey FRED API key (uses FRED_API_KEY env var if not provided)
     * @return array Array of date-value pairs
     * @throws RuntimeException if API key missing or request fails
     */
    public function getFRED(string $series, ?string $startDate = null, ?string $endDate = null, ?string $apiKey = null): array
    {
        if ($apiKey === null) {
            $apiKey = getenv('FRED_API_KEY');
            if (!$apiKey) {
                throw new RuntimeException('FRED API key not provided. Set FRED_API_KEY environment variable or pass it as parameter.');
            }
        }
        
        $params = [
            'series_id' => $series,
            'api_key' => $apiKey,
            'file_type' => 'json',
        ];
        
        if ($startDate) {
            $params['observation_start'] = $startDate;
        }
        
        if ($endDate) {
            $params['observation_end'] = $endDate;
        }
        
        $uri = $this->uriFactory->createUri('https://api.stlouisfed.org/fred/series/observations')
            ->withQuery(http_build_query($params));
        
        $url = (string) $uri;
        
        $cacheKey = 'fred_' . md5($url);
        
        if ($this->cache && $cachedData = $this->getFromCache($cacheKey)) {
            return $cachedData;
        }
        
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            foreach ($this->defaultHeaders as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
            
            $response = $this->httpClient->sendRequest($request);
            $data = json_decode($response->getBody()->getContents(), true);
            
            if (!isset($data['observations'])) {
                throw new RuntimeException('Invalid response from FRED API');
            }
            
            $result = [];
            foreach ($data['observations'] as $obs) {
                if ($obs['value'] !== '.') {
                    $result[] = [
                        'date' => $obs['date'],
                        'value' => (float)$obs['value'],
                    ];
                }
            }
            
            if ($this->cache) {
                $this->saveToCache($cacheKey, $result, 86400); // 24 hours
            }
            
            return $result;
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to fetch data from FRED: ' . $e->getMessage());
        }
    }
    
    /**
     * Fetches Robert Shiller's long-term market data.
     * 
     * Retrieves historical market data compiled by Nobel laureate Robert Shiller,
     * including:
     * - S&P 500 index and earnings
     * - Cyclically Adjusted PE Ratio (CAPE/Shiller PE)
     * - Long-term interest rates
     * - Historical dividends
     * 
     * Data spans from 1871 to present, useful for long-term market analysis
     * and valuation studies.
     * 
     * Note: Excel parsing requires additional PHP extensions. Consider using
     * CSV alternatives if Excel support is not available.
     * 
     * @return array Parsed historical market data or error message
     * @throws RuntimeException if download fails
     */
    public function getShillerData(): array
    {
        $uri = $this->uriFactory->createUri('http://www.econ.yale.edu/~shiller/data/ie_data.xls');
        $url = (string) $uri;
        
        $cacheKey = 'shiller_data';
        
        if ($this->cache && $cachedData = $this->getFromCache($cacheKey)) {
            return $cachedData;
        }
        
        $xlsContent = $this->downloadFile($url);
        
        $data = $this->parseShillerExcel($xlsContent);
        
        if ($this->cache) {
            $this->saveToCache($cacheKey, $data, 604800); // 7 days
        }
        
        return $data;
    }
    
    /**
     * Fetches historical price data from Yahoo Finance.
     * 
     * NOTE: The v7 download API now requires authentication. This method now uses
     * the v8 chart API which is still publicly accessible.
     * 
     * Retrieves daily OHLCV (Open, High, Low, Close, Volume) data
     * for stocks, ETFs, and indices.
     * 
     * Common symbols:
     * - Stocks: AAPL, MSFT, GOOGL
     * - Indices: ^GSPC (S&P 500), ^DJI (Dow Jones), ^IXIC (NASDAQ)
     * - ETFs: SPY, QQQ, IWM
     * 
     * @param string $symbol Yahoo Finance ticker symbol
     * @param string|null $startDate Start date (defaults to 1 year ago)
     * @param string|null $endDate End date (defaults to today)
     * @return array Array of daily price data with OHLCV
     * @throws RuntimeException if symbol is invalid or request fails
     */
    public function getYahooFinance(string $symbol, ?string $startDate = null, ?string $endDate = null): array
    {
        $startTimestamp = $startDate ? strtotime($startDate) : strtotime('-1 year');
        $endTimestamp = $endDate ? strtotime($endDate) : time();
        
        // Use v8 API instead of v7 (which now requires authentication)
        $params = [
            'period1' => $startTimestamp,
            'period2' => $endTimestamp,
            'interval' => '1d',
            'includePrePost' => 'false',
            'events' => 'div|split|earn',
        ];
        
        $uri = $this->uriFactory->createUri('https://query1.finance.yahoo.com/v8/finance/chart/' . urlencode($symbol))
            ->withQuery(http_build_query($params));
        
        $url = (string) $uri;
        
        $cacheKey = 'yahoo_' . md5($url);
        
        if ($this->cache && $cachedData = $this->getFromCache($cacheKey)) {
            return $cachedData;
        }
        
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            $request = $request
                ->withHeader('User-Agent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36')
                ->withHeader('Accept', 'application/json')
                ->withHeader('Accept-Language', 'en-US,en;q=0.9');
            
            $response = $this->httpClient->sendRequest($request);
            
            $jsonContent = $response->getBody()->getContents();
            $data = $this->parseYahooV8JSON($jsonContent);
            
            if ($this->cache) {
                $this->saveToCache($cacheKey, $data, 3600); // 1 hour
            }
            
            return $data;
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to fetch data from Yahoo Finance: ' . $e->getMessage());
        }
    }
    
    /**
     * Downloads file content from a URL.
     * 
     * Handles HTTP requests with proper headers and error handling.
     * 
     * @param string|UriInterface $url URL to download from
     * @return string Downloaded content
     * @throws RuntimeException if download fails
     */
    private function downloadFile(string|UriInterface $url): string
    {
        try {
            $request = $this->requestFactory->createRequest('GET', $url);
            foreach ($this->defaultHeaders as $name => $value) {
                $request = $request->withHeader($name, $value);
            }
            
            $response = $this->httpClient->sendRequest($request);
            return $response->getBody()->getContents();
        } catch (ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to download file: ' . $e->getMessage());
        }
    }
    
    /**
     * Extracts content from a ZIP file.
     * 
     * Used primarily for Fama-French data which is distributed as ZIP files.
     * Extracts the first file from the archive.
     * 
     * @param string $zipContent Binary ZIP content
     * @return string Extracted file content
     * @throws RuntimeException if extraction fails
     */
    private function extractZipContent(string $zipContent): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'phpfinance_zip');
        file_put_contents($tempFile, $zipContent);
        
        $zip = new \ZipArchive();
        if ($zip->open($tempFile) !== true) {
            unlink($tempFile);
            throw new RuntimeException('Failed to open zip file');
        }
        
        if ($zip->numFiles === 0) {
            $zip->close();
            unlink($tempFile);
            throw new RuntimeException('Zip file is empty');
        }
        
        $content = $zip->getFromIndex(0);
        $zip->close();
        unlink($tempFile);
        
        return $content;
    }
    
    /**
     * Parses Fama-French CSV format.
     * 
     * Handles the specific format of Fama-French data files which include:
     * - Header information and descriptions
     * - Monthly/daily data sections
     * - Multiple data columns (factors)
     * - Footer information
     * 
     * @param string $csvContent Raw CSV content
     * @return array Structured data with dates and values
     */
    private function parseFamaFrenchCSV(string $csvContent): array
    {
        $lines = explode("\n", $csvContent);
        $data = [];
        $inData = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            if (preg_match('/^\d{6}/', $line) || preg_match('/^\d{4}/', $line)) {
                $inData = true;
            }
            
            if (!$inData) {
                continue;
            }
            
            if (strpos($line, 'Copyright') !== false || strpos($line, 'Annual') !== false) {
                break;
            }
            
            $parts = preg_split('/\s+/', $line);
            
            if (count($parts) < 2) {
                continue;
            }
            
            $date = $parts[0];
            $values = array_slice($parts, 1);
            
            $data[] = [
                'date' => $date,
                'values' => array_map('floatval', $values),
            ];
        }
        
        return $data;
    }
    
    /**
     * Parses Yahoo Finance v8 API JSON response.
     * 
     * Processes the JSON response from the v8 chart API and converts
     * it to the same format as the old CSV API for compatibility.
     * Uses dot notation for cleaner nested data access.
     * 
     * @param string $jsonContent Raw JSON content from v8 API
     * @return array Array of daily price records
     * @throws RuntimeException if JSON parsing fails
     */
    private function parseYahooV8JSON(string $jsonContent): array
    {
        $data = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new RuntimeException('Failed to parse JSON response: ' . json_last_error_msg());
        }
        
        $dot = new Dot($data);
        
        // Use dot notation for cleaner access
        $timestamps = $dot->get('chart.result.0.timestamp', []);
        $quoteData = $dot->get('chart.result.0.indicators.quote.0', []);
        
        if (empty($timestamps) || empty($quoteData)) {
            throw new RuntimeException('Missing required data in Yahoo Finance response');
        }
        
        // Wrap quote data in Dot object for efficient nested access
        $quote = new Dot($quoteData);
        
        $ohlcv = [];
        
        for ($i = 0; $i < count($timestamps); $i++) {
            $ohlcv[] = [
                'Date' => date('Y-m-d', $timestamps[$i]),
                'Open' => $quote->get("open.$i"),
                'High' => $quote->get("high.$i"),
                'Low' => $quote->get("low.$i"),
                'Close' => $quote->get("close.$i"),
                'Volume' => $quote->get("volume.$i"),
            ];
        }
        
        return $ohlcv;
    }
    
    /**
     * Parses Yahoo Finance CSV format.
     * 
     * Processes standard Yahoo Finance historical data CSV with columns:
     * Date, Open, High, Low, Close, Adj Close, Volume
     * 
     * @param string $csvContent Raw CSV content
     * @return array Array of daily price records
     * @deprecated Use parseYahooV8JSON instead as CSV API requires authentication
     */
    private function parseYahooCSV(string $csvContent): array
    {
        $lines = explode("\n", $csvContent);
        $headers = str_getcsv(array_shift($lines), escape: '\\');

        $data = [];
        
        foreach ($lines as $line) {
            if (empty(trim($line))) {
                continue;
            }

            $row = str_getcsv($line, escape: '\\');

            if (count($row) !== count($headers)) {
                continue;
            }
            
            $entry = [];
            foreach ($headers as $i => $header) {
                $value = $row[$i];
                
                if ($header === 'Date') {
                    $entry[$header] = $value;
                } elseif ($value !== 'null' && $value !== '') {
                    $entry[$header] = (float)$value;
                } else {
                    $entry[$header] = null;
                }
            }
            
            $data[] = $entry;
        }
        
        return $data;
    }
    
    /**
     * Parses Shiller's Excel data file.
     * 
     * Currently returns an error message as Excel parsing requires
     * additional PHP extensions (PHPSpreadsheet or similar).
     * 
     * @param string $content Excel file content
     * @return array Error message or parsed data when implemented
     */
    private function parseShillerExcel(string $content): array
    {
        return [
            'error' => 'Excel parsing requires additional PHP extensions. Please use CSV alternative or implement Excel parser.',
        ];
    }
    
    /**
     * Retrieves data from cache.
     * 
     * Checks if cached data exists and is still valid.
     * 
     * @param string $key Cache key
     * @return array|null Cached data or null if not found/expired
     */
    private function getFromCache(string $key): ?array
    {
        if (!$this->cache) {
            return null;
        }
        
        try {
            $item = $this->cache->getItem($key);
            
            if ($item->isHit()) {
                return $item->get();
            }
            
            return null;
        } catch (CacheInvalidArgumentException $e) {
            return null;
        }
    }
    
    /**
     * Saves data to cache.
     * 
     * Stores fetched data in cache with specified time-to-live.
     * Silently fails if caching is unavailable or errors occur.
     * 
     * @param string $key Cache key
     * @param array $data Data to cache
     * @param int $ttl Time to live in seconds (default 24 hours)
     */
    private function saveToCache(string $key, array $data, int $ttl = 86400): void
    {
        if (!$this->cache) {
            return;
        }
        
        try {
            $item = $this->cache->getItem($key);
            $item->set($data);
            $item->expiresAfter($ttl);
            
            $this->cache->save($item);
        } catch (CacheInvalidArgumentException $e) {
            // Silently fail on cache errors
        }
    }
    
    /**
     * Clears all cached data.
     * 
     * Useful for forcing fresh data fetches or managing cache size.
     * Only clears cache if a cache pool is configured.
     */
    public function clearCache(): void
    {
        if ($this->cache) {
            $this->cache->clear();
        }
    }
}