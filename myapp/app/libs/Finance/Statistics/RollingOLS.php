<?php

declare(strict_types=1);

namespace Finance\Statistics;

use InvalidArgumentException;

/**
 * Rolling Window Ordinary Least Squares (Rolling OLS) regression implementation.
 * 
 * This class performs OLS regression on sequential overlapping windows of data,
 * allowing analysis of how regression relationships change over time. It's particularly
 * useful for time series analysis, detecting structural breaks, and understanding
 * dynamic relationships between variables.
 * 
 * For each window, the class calculates:
 * - Regression coefficients for that window
 * - R-squared and adjusted R-squared
 * - Standard errors, t-statistics, and p-values
 * - Window position indices for tracking
 * 
 * Results are stored for all windows, enabling time-series analysis of
 * coefficient stability, significance changes, and model fit evolution.
 */
class RollingOLS
{
    private array $y;
    private array $X;
    private array $results = [];
    
    /**
     * Constructs a Rolling OLS regression model.
     * 
     * @param array $y Dependent variable (response) values for the full dataset
     * @param array $X Independent variables (predictors). Can be 1D array for simple regression
     *                 or 2D array for multiple regression
     * @param int $window Size of the rolling window (number of observations per regression)
     * @param bool $hasIntercept Whether to include an intercept term in each regression (default: true)
     * @throws InvalidArgumentException if window size is less than 2 or exceeds data length
     */
    public function __construct(
        array $y, 
        array $X, 
        private int $window, 
        private bool $hasIntercept = true
    ) {
        if ($window < 2) {
            throw new InvalidArgumentException('Window size must be at least 2');
        }
        
        if (count($y) < $window) {
            throw new InvalidArgumentException('Data length must be greater than or equal to window size');
        }
        
        $this->y = array_values($y);
        $this->X = $X;
        
        $this->fit();
    }
    
    /**
     * Fits OLS models for all rolling windows.
     * 
     * Iterates through the data creating overlapping windows and fits
     * an OLS model to each window. Results are stored with window indices
     * for tracking. If a window fails to fit (e.g., singular matrix),
     * the error is captured and stored for that window.
     */
    private function fit(): void
    {
        $n = count($this->y);
        
        for ($i = 0; $i <= $n - $this->window; $i++) {
            $yWindow = array_slice($this->y, $i, $this->window);
            $XWindow = array_slice($this->X, $i, $this->window);
            
            try {
                $ols = new OLS($yWindow, $XWindow, $this->hasIntercept);
                
                $this->results[] = [
                    'start_index' => $i,
                    'end_index' => $i + $this->window - 1,
                    'coefficients' => $ols->getCoefficients(),
                    'r_squared' => $ols->getRSquared(),
                    'adjusted_r_squared' => $ols->getAdjustedRSquared(),
                    'standard_errors' => $ols->getStandardErrors(),
                    't_statistics' => $ols->getTStatistics(),
                    'p_values' => $ols->getPValues(),
                ];
            } catch (\Exception $e) {
                $this->results[] = [
                    'start_index' => $i,
                    'end_index' => $i + $this->window - 1,
                    'error' => $e->getMessage(),
                ];
            }
        }
    }
    
    /**
     * Returns complete results for all rolling windows.
     * 
     * Each result contains window indices, regression statistics,
     * or error information if the regression failed for that window.
     * 
     * @return array Array of results, one per window
     */
    public function getResults(): array
    {
        return $this->results;
    }
    
    /**
     * Returns coefficients for all windows.
     * 
     * Extracts coefficient arrays from each window's results.
     * Returns null for windows where regression failed.
     * 
     * @return array Array of coefficient arrays, one per window
     */
    public function getCoefficients(): array
    {
        $coefficients = [];
        
        foreach ($this->results as $result) {
            if (isset($result['coefficients'])) {
                $coefficients[] = $result['coefficients'];
            } else {
                $coefficients[] = null;
            }
        }
        
        return $coefficients;
    }
    
    /**
     * Returns R-squared values for all windows.
     * 
     * Useful for tracking model fit quality over time and
     * identifying periods where the model explains variance well.
     * 
     * @return array Array of R-squared values, one per window
     */
    public function getRSquared(): array
    {
        $rSquared = [];
        
        foreach ($this->results as $result) {
            if (isset($result['r_squared'])) {
                $rSquared[] = $result['r_squared'];
            } else {
                $rSquared[] = null;
            }
        }
        
        return $rSquared;
    }
    
    /**
     * Returns time series of a specific coefficient across all windows.
     * 
     * Extracts a single coefficient (e.g., intercept or specific predictor)
     * across all windows, enabling analysis of how that coefficient changes
     * over time. Useful for detecting parameter instability or trends.
     * 
     * @param int $coefficientIndex Index of the coefficient to extract (0 = intercept if present)
     * @return array Time series of the specified coefficient
     */
    public function getCoefficientSeries(int $coefficientIndex = 0): array
    {
        $series = [];
        
        foreach ($this->results as $result) {
            if (isset($result['coefficients']) && isset($result['coefficients'][$coefficientIndex])) {
                $series[] = $result['coefficients'][$coefficientIndex];
            } else {
                $series[] = null;
            }
        }
        
        return $series;
    }
    
    /**
     * Returns time series of standard errors for a specific coefficient.
     * 
     * Tracks uncertainty in coefficient estimates over time.
     * Increasing standard errors may indicate model instability
     * or changing relationships.
     * 
     * @param int $coefficientIndex Index of the coefficient (0 = intercept if present)
     * @return array Time series of standard errors
     */
    public function getStandardErrorSeries(int $coefficientIndex = 0): array
    {
        $series = [];
        
        foreach ($this->results as $result) {
            if (isset($result['standard_errors']) && isset($result['standard_errors'][$coefficientIndex])) {
                $series[] = $result['standard_errors'][$coefficientIndex];
            } else {
                $series[] = null;
            }
        }
        
        return $series;
    }
    
    /**
     * Returns time series of t-statistics for a specific coefficient.
     * 
     * Tracks statistical significance of a coefficient over time.
     * Values outside ±1.96 typically indicate significance at 5% level.
     * 
     * @param int $coefficientIndex Index of the coefficient (0 = intercept if present)
     * @return array Time series of t-statistics
     */
    public function getTStatisticSeries(int $coefficientIndex = 0): array
    {
        $series = [];
        
        foreach ($this->results as $result) {
            if (isset($result['t_statistics']) && isset($result['t_statistics'][$coefficientIndex])) {
                $series[] = $result['t_statistics'][$coefficientIndex];
            } else {
                $series[] = null;
            }
        }
        
        return $series;
    }
    
    /**
     * Returns time series of p-values for a specific coefficient.
     * 
     * Tracks significance levels over time. Useful for identifying
     * periods where a predictor becomes significant or loses significance.
     * 
     * @param int $coefficientIndex Index of the coefficient (0 = intercept if present)
     * @return array Time series of p-values
     */
    public function getPValueSeries(int $coefficientIndex = 0): array
    {
        $series = [];
        
        foreach ($this->results as $result) {
            if (isset($result['p_values']) && isset($result['p_values'][$coefficientIndex])) {
                $series[] = $result['p_values'][$coefficientIndex];
            } else {
                $series[] = null;
            }
        }
        
        return $series;
    }
    
    /**
     * Returns a comprehensive summary of the rolling regression analysis.
     * 
     * Includes configuration parameters (window size, intercept setting)
     * and complete results for all windows. Useful for understanding
     * the overall analysis setup and accessing all computed statistics.
     * 
     * @return array Summary including window parameters and all results
     */
    public function getSummary(): array
    {
        return [
            'window_size' => $this->window,
            'n_windows' => count($this->results),
            'has_intercept' => $this->hasIntercept,
            'total_observations' => count($this->y),
            'results' => $this->results,
        ];
    }
}