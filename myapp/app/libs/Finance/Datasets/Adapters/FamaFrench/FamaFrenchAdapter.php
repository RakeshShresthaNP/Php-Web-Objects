<?php

declare(strict_types=1);

namespace Finance\Datasets\Adapters\FamaFrench;

use Finance\Datasets\Adapters\AbstractAdapter;
use RuntimeException;
use InvalidArgumentException;
use ZipArchive;

/**
 * Fama-French data library adapter.
 * 
 * Provides access to academic factor data from Kenneth French's data library.
 * 
 * Features:
 * - Factor returns (3-factor, 5-factor, momentum)
 * - Portfolio returns sorted by various characteristics
 * - Industry portfolios
 * 
 * Future improvements:
 * - Return FactorData DTOs with metadata
 * - Add more dataset types
 * - Parse different data sections (monthly, daily, annual)
 */
class FamaFrenchAdapter extends AbstractAdapter
{
    private const BASE_URL = 'http://mba.tuck.dartmouth.edu/pages/faculty/ken.french/ftp/';
    
    /**
     * Available datasets with descriptions.
     */
    private const DATASETS = [
        'F-F_Research_Data_Factors' => '3-factor model (Market, SMB, HML) - Monthly',
        'F-F_Research_Data_Factors_daily' => '3-factor model - Daily',
        'F-F_Research_Data_5_Factors_2x3' => '5-factor model (Market, SMB, HML, RMW, CMA) - Monthly',
        'F-F_Research_Data_5_Factors_2x3_daily' => '5-factor model - Daily',
        'F-F_Momentum_Factor' => 'Momentum factor (UMD) - Monthly',
        'F-F_Momentum_Factor_daily' => 'Momentum factor - Daily',
        '6_Portfolios_2x3' => '6 portfolios formed on size and book-to-market',
        '25_Portfolios_5x5' => '25 portfolios formed on size and book-to-market',
        '100_Portfolios_10x10' => '100 portfolios formed on size and book-to-market',
    ];
    
    /**
     * {@inheritdoc}
     */
    public function getName(): string
    {
        return 'fama_french';
    }
    
    /**
     * {@inheritdoc}
     */
    public function getSupportedFeatures(): array
    {
        return ['factors', 'portfolios', 'academic_data'];
    }
    
    /**
     * {@inheritdoc}
     */
    public function getRequiredConfig(): array
    {
        return []; // No API key required
    }
    
    /**
     * Get available datasets.
     * 
     * @return array Dataset names and descriptions
     */
    public function getAvailableDatasets(): array
    {
        return self::DATASETS;
    }
    
    /**
     * Fetch Fama-French dataset.
     * 
     * @param string $dataset Dataset identifier
     * @return array Parsed factor/portfolio data
     * @throws InvalidArgumentException if dataset is invalid
     * @throws RuntimeException if download fails
     */
    public function getDataset(string $dataset): array
    {
        if (!isset(self::DATASETS[$dataset])) {
            throw new InvalidArgumentException(
                sprintf('Invalid dataset "%s". Available: %s', $dataset, implode(', ', array_keys(self::DATASETS)))
            );
        }
        
        // Check cache
        $cacheKey = 'dataset_' . md5($dataset);
        if ($cached = $this->getFromCache($cacheKey)) {
            return $cached;
        }
        
        $url = self::BASE_URL . $dataset . '_CSV.zip';
        $uri = $this->uriFactory->createUri($url);
        
        try {
            $request = $this->requestFactory->createRequest('GET', (string) $uri);
            $request = $request->withHeader('User-Agent', $this->defaultHeaders['User-Agent']);
            
            $response = $this->httpClient->sendRequest($request);
            $zipContent = $response->getBody()->getContents();
            
            $csvContent = $this->extractZipContent($zipContent);
            $data = $this->parseFamaFrenchCSV($csvContent, $dataset);
            
            // Cache for 24 hours (data updates infrequently)
            $this->saveToCache($cacheKey, $data, 86400);
            
            return $data;
            
        } catch (\Psr\Http\Client\ClientExceptionInterface $e) {
            throw new RuntimeException('Failed to fetch Fama-French data: ' . $e->getMessage());
        }
    }
    
    /**
     * Get factor returns (convenience method).
     * 
     * @param string $frequency 'daily' or 'monthly'
     * @param int $factors 3 or 5 factor model
     * @return array Factor returns data
     */
    public function getFactorReturns(string $frequency = 'monthly', int $factors = 3): array
    {
        $dataset = match ([$factors, $frequency]) {
            [3, 'monthly'] => 'F-F_Research_Data_Factors',
            [3, 'daily'] => 'F-F_Research_Data_Factors_daily',
            [5, 'monthly'] => 'F-F_Research_Data_5_Factors_2x3',
            [5, 'daily'] => 'F-F_Research_Data_5_Factors_2x3_daily',
            default => throw new InvalidArgumentException('Invalid factor/frequency combination')
        };
        
        return $this->getDataset($dataset);
    }
    
    /**
     * {@inheritdoc}
     */
    public function normalizeData(mixed $data): array
    {
        // Fama-French data has its own format, so we keep it as-is
        // Future: Convert to FactorData DTOs
        return is_array($data) ? $data : [];
    }
    
    /**
     * Extract content from ZIP file.
     * 
     * @param string $zipContent Binary ZIP content
     * @return string Extracted CSV content
     * @throws RuntimeException if extraction fails
     */
    protected function extractZipContent(string $zipContent): string
    {
        $tempFile = tempnam(sys_get_temp_dir(), 'famafrench_');
        file_put_contents($tempFile, $zipContent);
        
        $zip = new ZipArchive();
        if ($zip->open($tempFile) !== true) {
            unlink($tempFile);
            throw new RuntimeException('Failed to open ZIP file');
        }
        
        if ($zip->numFiles === 0) {
            $zip->close();
            unlink($tempFile);
            throw new RuntimeException('ZIP file is empty');
        }
        
        $content = $zip->getFromIndex(0);
        $zip->close();
        unlink($tempFile);
        
        return $content;
    }
    
    /**
     * Parse Fama-French CSV format.
     * 
     * @param string $csvContent Raw CSV content
     * @param string $dataset Dataset name for context
     * @return array Structured data
     */
    protected function parseFamaFrenchCSV(string $csvContent, string $dataset): array
    {
        $lines = explode("\n", $csvContent);
        $data = [];
        $headers = [];
        $inDataSection = false;
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            if (empty($line)) {
                continue;
            }
            
            // Detect start of data section (line starting with date)
            if (!$inDataSection && (preg_match('/^\d{6}/', $line) || preg_match('/^\d{4}/', $line))) {
                $inDataSection = true;
                
                // Determine headers based on dataset
                if (str_contains($dataset, '5_Factors')) {
                    $headers = ['date', 'Mkt-RF', 'SMB', 'HML', 'RMW', 'CMA', 'RF'];
                } elseif (str_contains($dataset, 'Momentum')) {
                    $headers = ['date', 'Mom'];
                } elseif (str_contains($dataset, 'Factors')) {
                    $headers = ['date', 'Mkt-RF', 'SMB', 'HML', 'RF'];
                } else {
                    // Portfolio data - headers will be numeric
                    $headers = ['date'];
                }
            }
            
            if (!$inDataSection) {
                continue;
            }
            
            // Stop at footer
            if (stripos($line, 'Copyright') !== false || stripos($line, 'Annual') !== false) {
                break;
            }
            
            $parts = preg_split('/\s+/', $line);
            
            if (count($parts) < 2) {
                continue;
            }
            
            $row = ['date' => $parts[0]];
            
            // Parse values - divide by 100 to get decimal returns
            for ($i = 1; $i < count($parts); $i++) {
                $key = $headers[$i] ?? 'col_' . $i;
                $row[$key] = floatval($parts[$i]) / 100;
            }
            
            // Future: Return FactorReturn DTO
            $data[] = $row;
        }
        
        return [
            'dataset' => $dataset,
            'description' => self::DATASETS[$dataset] ?? '',
            'data' => $data,
            'count' => count($data),
        ];
    }
}