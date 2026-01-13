<?php

declare(strict_types=1);

namespace Finance\General;

use InvalidArgumentException;
use MathPHP\Probability\Distribution\Continuous\Normal;

/**
 * Financial utilities for portfolio analysis and risk management.
 * 
 * This class provides a collection of static methods for common financial
 * calculations including:
 * - Portfolio performance metrics (active share, compound returns)
 * - Risk measures (VaR, CVaR, maximum drawdown)
 * - Statistical analysis (correlation, distribution generation)
 * - Position sizing (Kelly formula)
 * - Performance ratios (Calmar ratio, annualized metrics)
 * 
 * All methods are static and can be used independently without instantiation.
 * Methods handle edge cases and validate inputs to ensure reliable calculations.
 */
class Utils
{
    /**
     * Calculates active share between portfolio and benchmark.
     * 
     * Active share measures the percentage of portfolio holdings that differ
     * from the benchmark. It ranges from 0% (identical to benchmark) to 100%
     * (completely different). Values above 60% typically indicate active management.
     * 
     * Formula: Active Share = 0.5 * Σ|w_p,i - w_b,i|
     * 
     * @param array $portfolio Associative array of asset => weight for portfolio
     * @param array $benchmark Associative array of asset => weight for benchmark
     * @return float Active share (0 to 1, where 1 = 100% active)
     * @throws InvalidArgumentException if arrays are empty or weights don't sum to 1
     */
    public static function activeShare(array $portfolio, array $benchmark): float
    {
        if (empty($portfolio) || empty($benchmark)) {
            throw new InvalidArgumentException('Portfolio and benchmark arrays cannot be empty');
        }
        
        $portfolioSum = array_sum($portfolio);
        $benchmarkSum = array_sum($benchmark);
        
        if (abs($portfolioSum - 1.0) > 0.01 || abs($benchmarkSum - 1.0) > 0.01) {
            throw new InvalidArgumentException('Portfolio and benchmark weights must sum to 1');
        }
        
        $allAssets = array_unique(array_merge(array_keys($portfolio), array_keys($benchmark)));
        $activeShare = 0.0;
        
        foreach ($allAssets as $asset) {
            $portfolioWeight = $portfolio[$asset] ?? 0.0;
            $benchmarkWeight = $benchmark[$asset] ?? 0.0;
            $activeShare += abs($portfolioWeight - $benchmarkWeight);
        }
        
        return $activeShare / 2.0;
    }
    
    /**
     * Generates a distribution of returns with specified statistical properties.
     * 
     * Creates synthetic return data matching target mean, standard deviation,
     * skewness, and kurtosis. Useful for Monte Carlo simulations, stress testing,
     * and scenario analysis.
     * 
     * @param float $mean Target mean return
     * @param float $std Target standard deviation (volatility)
     * @param float $skew Target skewness (0 = symmetric, >0 = right tail, <0 = left tail)
     * @param float $kurt Target kurtosis (3 = normal, >3 = fat tails)
     * @param int $n Number of samples to generate
     * @return array Array of simulated returns
     * @throws InvalidArgumentException if std <= 0 or n <= 0
     */
    public static function returnsDistribution(
        float $mean,
        float $std,
        float $skew = 0.0,
        float $kurt = 3.0,
        int $n = 1000
    ): array {
        if ($std <= 0) {
            throw new InvalidArgumentException('Standard deviation must be positive');
        }
        
        if ($n <= 0) {
            throw new InvalidArgumentException('Number of samples must be positive');
        }
        
        $normal = new Normal($mean, $std);
        $samples = [];
        
        for ($i = 0; $i < $n; $i++) {
            $samples[] = $normal->rand();
        }
        
        if ($skew != 0.0 || $kurt != 3.0) {
            $samples = self::adjustSkewKurtosis($samples, $mean, $std, $skew, $kurt);
        }
        
        return $samples;
    }
    
    /**
     * Adjusts a sample distribution to match target skewness and kurtosis.
     * 
     * Uses Cornish-Fisher expansion to transform normal samples into
     * a distribution with desired higher moments. This approximation
     * works well for moderate skewness and excess kurtosis.
     * 
     * @param array $samples Original samples
     * @param float $targetMean Desired mean
     * @param float $targetStd Desired standard deviation
     * @param float $targetSkew Desired skewness
     * @param float $targetKurt Desired kurtosis
     * @return array Adjusted samples
     */
    private static function adjustSkewKurtosis(
        array $samples,
        float $targetMean,
        float $targetStd,
        float $targetSkew,
        float $targetKurt
    ): array {
        $z = self::standardize($samples);
        
        $a = $targetSkew / 6.0;
        $b = ($targetKurt - 3.0) / 24.0;
        
        $adjusted = [];
        foreach ($z as $zi) {
            $yi = $zi + $a * (pow($zi, 2) - 1) + $b * (pow($zi, 3) - 3 * $zi);
            $adjusted[] = $yi;
        }
        
        $adjusted = self::standardize($adjusted);
        
        $result = [];
        foreach ($adjusted as $val) {
            $result[] = $val * $targetStd + $targetMean;
        }
        
        return $result;
    }
    
    /**
     * Standardizes data to zero mean and unit variance.
     * 
     * Z-score normalization: z = (x - μ) / σ
     * 
     * @param array $data Raw data values
     * @return array Standardized values with mean=0, std=1
     */
    private static function standardize(array $data): array
    {
        $mean = array_sum($data) / count($data);
        $variance = 0.0;
        
        foreach ($data as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $std = sqrt($variance / count($data));
        
        if ($std == 0) {
            return array_fill(0, count($data), 0.0);
        }
        
        $standardized = [];
        foreach ($data as $value) {
            $standardized[] = ($value - $mean) / $std;
        }
        
        return $standardized;
    }
    
    /**
     * Calculates optimal bet size using the Kelly Criterion.
     * 
     * The Kelly formula determines the optimal fraction of capital to bet
     * to maximize long-term growth rate. It balances risk and return optimally
     * under the assumption of known probabilities and payoffs.
     * 
     * Formula: f* = (pb - q) / b
     * where p = win probability, q = loss probability, b = win/loss ratio
     * 
     * @param float $winProbability Probability of winning (0 to 1)
     * @param float $winAmount Amount won on successful bet
     * @param float $lossAmount Amount lost on unsuccessful bet
     * @return float Optimal fraction of capital to bet (can be negative if edge is negative)
     * @throws InvalidArgumentException if probability not in [0,1] or amounts not positive
     */
    public static function kellyFormula(
        float $winProbability,
        float $winAmount,
        float $lossAmount
    ): float {
        if ($winProbability < 0 || $winProbability > 1) {
            throw new InvalidArgumentException('Win probability must be between 0 and 1');
        }
        
        if ($winAmount <= 0 || $lossAmount <= 0) {
            throw new InvalidArgumentException('Win and loss amounts must be positive');
        }
        
        $b = $winAmount / $lossAmount;
        $p = $winProbability;
        $q = 1 - $p;
        
        if ($b == 0) {
            return 0.0;
        }
        
        return ($p * $b - $q) / $b;
    }
    
    /**
     * Calculates the total compound return from a series of returns.
     * 
     * Compounds individual period returns to get total return.
     * Formula: (1 + r1) * (1 + r2) * ... * (1 + rn) - 1
     * 
     * @param array $returns Array of period returns (e.g., 0.05 for 5%)
     * @return float Total compound return
     * @throws InvalidArgumentException if returns array is empty
     */
    public static function compoundReturn(array $returns): float
    {
        if (empty($returns)) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
        
        $product = 1.0;
        foreach ($returns as $return) {
            $product *= (1 + $return);
        }
        
        return $product - 1.0;
    }
    
    /**
     * Calculates the geometric mean return.
     * 
     * The geometric mean properly accounts for compounding and is the
     * appropriate measure for investment returns over multiple periods.
     * It represents the constant rate of return that would yield the
     * same final value.
     * 
     * Formula: [(1 + r1) * (1 + r2) * ... * (1 + rn)]^(1/n) - 1
     * 
     * @param array $returns Array of period returns
     * @return float Geometric mean return
     * @throws InvalidArgumentException if returns empty or any return <= -100%
     */
    public static function geometricMean(array $returns): float
    {
        if (empty($returns)) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
        
        $product = 1.0;
        foreach ($returns as $return) {
            if ($return <= -1) {
                throw new InvalidArgumentException('Returns must be greater than -100%');
            }
            $product *= (1 + $return);
        }
        
        return pow($product, 1.0 / count($returns)) - 1.0;
    }
    
    /**
     * Calculates Value at Risk (VaR) using historical simulation.
     * 
     * VaR estimates the maximum loss that won't be exceeded with a given
     * confidence level over a specific time period. For example, 95% VaR
     * of -5% means there's only a 5% chance of losing more than 5%.
     * 
     * @param array $returns Historical returns
     * @param float $confidence Confidence level (e.g., 0.95 for 95%)
     * @return float VaR threshold (typically negative for losses)
     * @throws InvalidArgumentException if returns empty or confidence not in (0,1)
     */
    public static function valueAtRisk(array $returns, float $confidence = 0.95): float
    {
        if (empty($returns)) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
        
        if ($confidence <= 0 || $confidence >= 1) {
            throw new InvalidArgumentException('Confidence level must be between 0 and 1');
        }
        
        sort($returns);
        $index = (int)floor((1 - $confidence) * count($returns));
        
        if ($index >= count($returns)) {
            $index = count($returns) - 1;
        }
        
        return $returns[$index];
    }
    
    /**
     * Calculates Conditional Value at Risk (CVaR/Expected Shortfall).
     * 
     * CVaR measures the expected loss given that the loss exceeds the VaR
     * threshold. It provides information about tail risk and is a coherent
     * risk measure (unlike VaR).
     * 
     * @param array $returns Historical returns
     * @param float $confidence Confidence level (e.g., 0.95 for 95%)
     * @return float Expected loss in the tail (typically negative)
     * @throws InvalidArgumentException if returns empty or confidence not in (0,1)
     */
    public static function conditionalValueAtRisk(array $returns, float $confidence = 0.95): float
    {
        if (empty($returns)) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
        
        if ($confidence <= 0 || $confidence >= 1) {
            throw new InvalidArgumentException('Confidence level must be between 0 and 1');
        }
        
        $var = self::valueAtRisk($returns, $confidence);
        
        $tailReturns = array_filter($returns, fn($r) => $r <= $var);
        
        if (empty($tailReturns)) {
            return $var;
        }
        
        return array_sum($tailReturns) / count($tailReturns);
    }
    
    /**
     * Calculates maximum drawdown from a price series.
     * 
     * Maximum drawdown measures the largest peak-to-trough decline
     * in value. It's a key risk metric showing the worst historical
     * loss an investor would have experienced.
     * 
     * @param array $prices Array of prices or portfolio values
     * @return float Maximum drawdown (negative percentage, e.g., -0.2 for 20% drawdown)
     * @throws InvalidArgumentException if prices array is empty
     */
    public static function maxDrawdown(array $prices): float
    {
        if (empty($prices)) {
            throw new InvalidArgumentException('Prices array cannot be empty');
        }
        
        $maxDrawdown = 0.0;
        $peak = $prices[0];
        
        foreach ($prices as $price) {
            if ($price > $peak) {
                $peak = $price;
            }
            
            $drawdown = ($price - $peak) / $peak;
            
            if ($drawdown < $maxDrawdown) {
                $maxDrawdown = $drawdown;
            }
        }
        
        return $maxDrawdown;
    }
    
    /**
     * Calculates the Calmar ratio.
     * 
     * The Calmar ratio measures risk-adjusted returns by dividing
     * annualized return by maximum drawdown. Higher values indicate
     * better risk-adjusted performance. Typically calculated over 3 years.
     * 
     * Formula: Annualized Return / |Maximum Drawdown|
     * 
     * @param array $returns Period returns
     * @param int $periods Periods per year (252 for daily, 12 for monthly)
     * @return float Calmar ratio
     * @throws InvalidArgumentException if returns array is empty
     */
    public static function calmarRatio(array $returns, int $periods = 252): float
    {
        if (empty($returns)) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
        
        $annualReturn = self::annualizedReturn($returns, $periods);
        
        $cumReturns = [];
        $cumReturn = 1.0;
        foreach ($returns as $return) {
            $cumReturn *= (1 + $return);
            $cumReturns[] = $cumReturn;
        }
        
        $maxDD = abs(self::maxDrawdown($cumReturns));
        
        if ($maxDD == 0) {
            return 0.0;
        }
        
        return $annualReturn / $maxDD;
    }
    
    /**
     * Calculates annualized return from period returns.
     * 
     * Converts returns of any frequency to annual equivalent,
     * properly accounting for compounding.
     * 
     * @param array $returns Period returns
     * @param int $periods Number of periods per year (252 for daily, 52 for weekly, 12 for monthly)
     * @return float Annualized return
     * @throws InvalidArgumentException if returns array is empty
     */
    public static function annualizedReturn(array $returns, int $periods = 252): float
    {
        if (empty($returns)) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
        
        $totalReturn = self::compoundReturn($returns);
        $years = count($returns) / $periods;
        
        if ($years <= 0) {
            return 0.0;
        }
        
        return pow(1 + $totalReturn, 1 / $years) - 1;
    }
    
    /**
     * Calculates annualized volatility from period returns.
     * 
     * Scales period volatility to annual equivalent using square root of time.
     * Assumes returns are independently distributed (no autocorrelation).
     * 
     * Formula: σ_annual = σ_period * √periods
     * 
     * @param array $returns Period returns
     * @param int $periods Number of periods per year
     * @return float Annualized volatility (standard deviation)
     * @throws InvalidArgumentException if returns array is empty
     */
    public static function annualizedVolatility(array $returns, int $periods = 252): float
    {
        if (empty($returns)) {
            throw new InvalidArgumentException('Returns array cannot be empty');
        }
        
        $mean = array_sum($returns) / count($returns);
        $variance = 0.0;
        
        foreach ($returns as $return) {
            $variance += pow($return - $mean, 2);
        }
        
        $std = sqrt($variance / (count($returns) - 1));
        
        return $std * sqrt($periods);
    }
    
    /**
     * Calculates Pearson correlation coefficient between two series.
     * 
     * Measures linear relationship between two variables.
     * Range: -1 (perfect negative) to +1 (perfect positive),
     * with 0 indicating no linear relationship.
     * 
     * Formula: ρ = Cov(X,Y) / (σ_X * σ_Y)
     * 
     * @param array $x First data series
     * @param array $y Second data series
     * @return float Correlation coefficient (-1 to 1)
     * @throws InvalidArgumentException if arrays empty or different lengths
     */
    public static function correlation(array $x, array $y): float
    {
        if (empty($x) || empty($y)) {
            throw new InvalidArgumentException('Input arrays cannot be empty');
        }
        
        if (count($x) !== count($y)) {
            throw new InvalidArgumentException('Arrays must have the same length');
        }
        
        $n = count($x);
        $meanX = array_sum($x) / $n;
        $meanY = array_sum($y) / $n;
        
        $numerator = 0.0;
        $denomX = 0.0;
        $denomY = 0.0;
        
        for ($i = 0; $i < $n; $i++) {
            $diffX = $x[$i] - $meanX;
            $diffY = $y[$i] - $meanY;
            
            $numerator += $diffX * $diffY;
            $denomX += pow($diffX, 2);
            $denomY += pow($diffY, 2);
        }
        
        if ($denomX == 0 || $denomY == 0) {
            return 0.0;
        }
        
        return $numerator / sqrt($denomX * $denomY);
    }
}