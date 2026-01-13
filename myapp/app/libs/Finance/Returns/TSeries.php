<?php

declare(strict_types=1);

namespace Finance\Returns;

use InvalidArgumentException;
use DateTime;
use DateTimeInterface;

/**
 * Time Series data structure for financial returns analysis.
 * 
 * This class provides a comprehensive suite of tools for analyzing financial
 * return series, including performance metrics, risk measures, and comparative
 * statistics. It handles time-indexed data with support for various frequencies
 * (daily, weekly, monthly, etc.) and provides methods for:
 * 
 * - Basic statistics (mean, std dev, min, max)
 * - Return calculations (cumulative, annualized, excess)
 * - Risk metrics (Sharpe ratio, Sortino ratio, maximum drawdown)
 * - Performance attribution (alpha, beta, tracking error, information ratio)
 * - Time series transformations (cumulative sum/product, return indices)
 * 
 * The class maintains a monotonically increasing index for time ordering
 * and supports frequency-aware annualization of metrics.
 */
class TSeries
{
    private array $data;
    private array $index;
    
    /**
     * Constructs a TSeries object for financial time series analysis.
     * 
     * @param array $data The time series values (e.g., returns, prices)
     * @param array $index Optional time index (must be monotonically increasing)
     * @param string|null $frequency Data frequency ('D', 'W', 'M', 'Q', 'Y', or descriptive)
     * @throws InvalidArgumentException if data is empty or index/data lengths mismatch
     */
    public function __construct(
        array $data, 
        array $index = [], 
        private ?string $frequency = null
    ) {
        if (empty($data)) {
            throw new InvalidArgumentException('Data array cannot be empty');
        }
        
        if (!empty($index) && count($data) !== count($index)) {
            throw new InvalidArgumentException('Data and index arrays must have the same length');
        }
        
        $this->data = array_values($data);
        $this->index = empty($index) ? range(0, count($data) - 1) : array_values($index);
        
        $this->validateMonotonicIndex();
    }
    
    /**
     * Validates that the index is strictly monotonically increasing.
     * 
     * Ensures time series data maintains proper temporal ordering,
     * which is critical for time-based calculations and analysis.
     * 
     * @throws InvalidArgumentException if index is not strictly increasing
     */
    private function validateMonotonicIndex(): void
    {
        $previous = null;
        foreach ($this->index as $value) {
            if ($previous !== null && $value <= $previous) {
                throw new InvalidArgumentException('Index must be strictly monotonically increasing');
            }
            $previous = $value;
        }
    }
    
    /**
     * Returns the raw data values.
     * @return array The time series data values
     */
    public function getData(): array
    {
        return $this->data;
    }
    
    /**
     * Returns the time index.
     * @return array The time index values
     */
    public function getIndex(): array
    {
        return $this->index;
    }
    
    /**
     * Returns the number of observations in the series.
     * @return int Count of data points
     */
    public function count(): int
    {
        return count($this->data);
    }
    
    /**
     * Calculates the sum of all values.
     * @return float Sum of the series
     */
    public function sum(): float
    {
        return array_sum($this->data);
    }
    
    /**
     * Calculates the arithmetic mean of the series.
     * @return float Mean value
     */
    public function mean(): float
    {
        return $this->sum() / $this->count();
    }
    
    /**
     * Calculates the standard deviation of the series.
     * 
     * Measures the dispersion of returns, commonly used as a
     * volatility proxy in financial analysis.
     * 
     * @param bool $sample Whether to use sample (n-1) or population (n) denominator
     * @return float Standard deviation
     */
    public function std(bool $sample = true): float
    {
        $mean = $this->mean();
        $variance = 0.0;
        
        foreach ($this->data as $value) {
            $variance += pow($value - $mean, 2);
        }
        
        $denominator = $sample ? ($this->count() - 1) : $this->count();
        
        if ($denominator <= 0) {
            return 0.0;
        }
        
        return sqrt($variance / $denominator);
    }
    
    /**
     * Calculates the variance of the series.
     * 
     * @param bool $sample Whether to use sample or population variance
     * @return float Variance (squared standard deviation)
     */
    public function variance(bool $sample = true): float
    {
        return pow($this->std($sample), 2);
    }
    
    /**
     * Returns the minimum value in the series.
     * @return float Minimum value
     */
    public function min(): float
    {
        return min($this->data);
    }
    
    /**
     * Returns the maximum value in the series.
     * @return float Maximum value
     */
    public function max(): float
    {
        return max($this->data);
    }
    
    /**
     * Calculates the cumulative sum of the series.
     * 
     * Useful for aggregating returns or tracking cumulative performance.
     * 
     * @return TSeries New series with cumulative sums
     */
    public function cumsum(): TSeries
    {
        $cumulative = [];
        $sum = 0;
        
        foreach ($this->data as $value) {
            $sum += $value;
            $cumulative[] = $sum;
        }
        
        return new TSeries($cumulative, $this->index, $this->frequency);
    }
    
    /**
     * Calculates the cumulative product of the series.
     * 
     * Essential for compounding returns and calculating growth factors.
     * 
     * @return TSeries New series with cumulative products
     */
    public function cumprod(): TSeries
    {
        $cumulative = [];
        $product = 1;
        
        foreach ($this->data as $value) {
            $product *= $value;
            $cumulative[] = $product;
        }
        
        return new TSeries($cumulative, $this->index, $this->frequency);
    }
    
    /**
     * Converts returns to return relatives (1 + return).
     * 
     * Return relatives are used for compounding calculations,
     * where a return of 5% becomes 1.05.
     * 
     * @return TSeries Series of return relatives
     */
    public function retRels(): TSeries
    {
        $rels = array_map(fn($x) => 1 + $x, $this->data);
        return new TSeries($rels, $this->index, $this->frequency);
    }
    
    /**
     * Creates a return index (wealth index) from the return series.
     * 
     * Shows the growth of an initial investment over time.
     * A value of 1.5 means 50% cumulative growth from the base.
     * 
     * @param float $base Starting value of the index (typically 1.0 or 100)
     * @return TSeries Return index series
     */
    public function retIdx(float $base = 1.0): TSeries
    {
        $rels = $this->retRels();
        $cumProd = $rels->cumprod();
        $idx = array_map(fn($x) => $x * $base, $cumProd->getData());
        
        return new TSeries($idx, $this->index, $this->frequency);
    }
    
    /**
     * Calculates the annualized return of the series.
     * 
     * Converts period returns to annual equivalent, accounting for
     * compounding. Frequency-aware for proper annualization.
     * 
     * @return float Annualized return (e.g., 0.08 for 8% annual return)
     */
    public function anlzdRet(): float
    {
        $periods = $this->inferPeriods();
        $totalReturn = $this->retIdx()->getData()[count($this->data) - 1] / 1.0;
        
        if ($periods <= 0) {
            return 0.0;
        }
        
        return pow($totalReturn, 1.0 / $periods) - 1.0;
    }
    
    /**
     * Calculates the total cumulative return.
     * 
     * The total return from start to end of the series.
     * 
     * @return float Cumulative return (e.g., 0.5 for 50% total return)
     */
    public function cumRet(): float
    {
        $idx = $this->retIdx()->getData();
        return end($idx) - 1.0;
    }
    
    /**
     * Calculates excess returns over a benchmark.
     * 
     * Excess returns measure outperformance relative to a benchmark,
     * used in risk-adjusted performance metrics.
     * 
     * @param TSeries|array|float $benchmark Benchmark returns or constant rate
     * @return TSeries Series of excess returns
     * @throws InvalidArgumentException if benchmark format is invalid
     */
    public function excessRet($benchmark): TSeries
    {
        if ($benchmark instanceof TSeries) {
            $benchData = $benchmark->getData();
        } elseif (is_array($benchmark)) {
            $benchData = $benchmark;
        } elseif (is_numeric($benchmark)) {
            $benchData = array_fill(0, $this->count(), $benchmark);
        } else {
            throw new InvalidArgumentException('Benchmark must be TSeries, array, or numeric');
        }
        
        if (count($benchData) !== $this->count()) {
            throw new InvalidArgumentException('Benchmark and series must have same length');
        }
        
        $excess = [];
        for ($i = 0; $i < $this->count(); $i++) {
            $excess[] = $this->data[$i] - $benchData[$i];
        }
        
        return new TSeries($excess, $this->index, $this->frequency);
    }
    
    /**
     * Calculates the Sharpe ratio for risk-adjusted performance.
     * 
     * Measures excess return per unit of risk (standard deviation).
     * Higher values indicate better risk-adjusted performance.
     * 
     * @param float|null $riskFreeRate Risk-free rate (annual if anlzd=true)
     * @param bool $anlzd Whether to annualize the ratio
     * @return float Sharpe ratio
     */
    public function sharpeRatio(?float $riskFreeRate = null, bool $anlzd = true): float
    {
        $riskFreeRate = $riskFreeRate ?? 0.0;
        
        if ($anlzd) {
            $periods = $this->inferPeriods();
            $adjustedRfr = $riskFreeRate / $periods;
            $excess = $this->excessRet($adjustedRfr);
            $meanExcess = $excess->mean();
            $stdExcess = $excess->std();
            
            if ($stdExcess == 0) {
                return 0.0;
            }
            
            return ($meanExcess * $periods) / ($stdExcess * sqrt($periods));
        } else {
            $excess = $this->excessRet($riskFreeRate);
            $meanExcess = $excess->mean();
            $stdExcess = $excess->std();
            
            if ($stdExcess == 0) {
                return 0.0;
            }
            
            return $meanExcess / $stdExcess;
        }
    }
    
    /**
     * Calculates beta relative to a benchmark.
     * 
     * Beta measures systematic risk - the sensitivity of returns
     * to benchmark movements. Beta > 1 implies higher volatility
     * than the benchmark.
     * 
     * @param TSeries|array $benchmark Benchmark return series
     * @return float Beta coefficient
     * @throws InvalidArgumentException if benchmark format/length is invalid
     */
    public function beta($benchmark): float
    {
        if ($benchmark instanceof TSeries) {
            $benchData = $benchmark->getData();
        } elseif (is_array($benchmark)) {
            $benchData = $benchmark;
        } else {
            throw new InvalidArgumentException('Benchmark must be TSeries or array');
        }
        
        if (count($benchData) !== $this->count()) {
            throw new InvalidArgumentException('Benchmark and series must have same length');
        }
        
        $meanSeries = $this->mean();
        $meanBench = array_sum($benchData) / count($benchData);
        
        $covariance = 0.0;
        $benchVariance = 0.0;
        
        for ($i = 0; $i < $this->count(); $i++) {
            $covariance += ($this->data[$i] - $meanSeries) * ($benchData[$i] - $meanBench);
            $benchVariance += pow($benchData[$i] - $meanBench, 2);
        }
        
        if ($benchVariance == 0) {
            return 0.0;
        }
        
        return $covariance / $benchVariance;
    }
    
    /**
     * Calculates Jensen's alpha relative to a benchmark.
     * 
     * Alpha measures excess return after adjusting for systematic risk.
     * Positive alpha indicates outperformance beyond what beta would predict.
     * 
     * @param TSeries|array $benchmark Benchmark return series
     * @param float|null $riskFreeRate Risk-free rate (annualized)
     * @return float Alpha (annualized excess return)
     */
    public function alpha($benchmark, ?float $riskFreeRate = null): float
    {
        $riskFreeRate = $riskFreeRate ?? 0.0;
        $beta = $this->beta($benchmark);
        
        if ($benchmark instanceof TSeries) {
            $benchReturn = $benchmark->anlzdRet();
        } else {
            $benchSeries = new TSeries($benchmark);
            $benchReturn = $benchSeries->anlzdRet();
        }
        
        $seriesReturn = $this->anlzdRet();
        
        return $seriesReturn - $riskFreeRate - $beta * ($benchReturn - $riskFreeRate);
    }
    
    /**
     * Calculates the drawdown series.
     * 
     * Drawdown measures the decline from historical peak,
     * expressed as a percentage. Used to assess downside risk.
     * 
     * @return TSeries Series of drawdown percentages (negative values)
     */
    public function drawdownIdx(): TSeries
    {
        $retIdx = $this->retIdx()->getData();
        $runningMax = [];
        $maxSoFar = $retIdx[0];
        
        foreach ($retIdx as $value) {
            $maxSoFar = max($maxSoFar, $value);
            $runningMax[] = $maxSoFar;
        }
        
        $drawdowns = [];
        for ($i = 0; $i < count($retIdx); $i++) {
            if ($runningMax[$i] == 0) {
                $drawdowns[] = 0.0;
            } else {
                $drawdowns[] = ($retIdx[$i] - $runningMax[$i]) / $runningMax[$i];
            }
        }
        
        return new TSeries($drawdowns, $this->index, $this->frequency);
    }
    
    /**
     * Calculates the maximum drawdown.
     * 
     * The largest peak-to-trough decline in the series history.
     * Key risk metric for understanding worst-case scenarios.
     * 
     * @return float Maximum drawdown (negative percentage)
     */
    public function maxDrawdown(): float
    {
        $drawdowns = $this->drawdownIdx()->getData();
        return min($drawdowns);
    }
    
    /**
     * Calculates tracking error relative to a benchmark.
     * 
     * Measures the standard deviation of excess returns,
     * indicating how closely the series follows the benchmark.
     * 
     * @param TSeries|array|float $benchmark Benchmark returns
     * @return float Annualized tracking error
     */
    public function trackingError($benchmark): float
    {
        $excess = $this->excessRet($benchmark);
        return $excess->std() * sqrt($this->inferPeriods());
    }
    
    /**
     * Calculates the information ratio.
     * 
     * Measures excess return per unit of tracking error,
     * evaluating the consistency of active management.
     * 
     * @param TSeries|array|float $benchmark Benchmark returns
     * @return float Information ratio
     */
    public function infoRatio($benchmark): float
    {
        $excess = $this->excessRet($benchmark);
        $te = $this->trackingError($benchmark);
        
        if ($te == 0) {
            return 0.0;
        }
        
        return $excess->mean() * $this->inferPeriods() / $te;
    }
    
    /**
     * Calculates the Sortino ratio.
     * 
     * Similar to Sharpe ratio but uses downside deviation,
     * focusing only on harmful volatility below the target.
     * 
     * @param float $target Minimum acceptable return (MAR)
     * @param bool $anlzd Whether to annualize the ratio
     * @return float Sortino ratio
     */
    public function sortino($target = 0.0, bool $anlzd = true): float
    {
        $excess = array_map(fn($x) => $x - $target, $this->data);
        $downsideReturns = array_filter($excess, fn($x) => $x < 0);
        
        if (empty($downsideReturns)) {
            return 0.0;
        }
        
        $downsideVariance = array_sum(array_map(fn($x) => pow($x, 2), $downsideReturns)) / count($this->data);
        $downsideDeviation = sqrt($downsideVariance);
        
        if ($downsideDeviation == 0) {
            return 0.0;
        }
        
        if ($anlzd) {
            $periods = $this->inferPeriods();
            $meanExcess = array_sum($excess) / count($excess);
            return ($meanExcess * $periods) / ($downsideDeviation * sqrt($periods));
        } else {
            $meanExcess = array_sum($excess) / count($excess);
            return $meanExcess / $downsideDeviation;
        }
    }
    
    /**
     * Infers the number of periods per year for annualization.
     * 
     * Uses the specified frequency or defaults based on data count.
     * Critical for proper annualization of returns and risk metrics.
     * 
     * @return float Number of periods per year
     */
    private function inferPeriods(): float
    {
        if ($this->frequency !== null) {
            return $this->getPeriodsFromFrequency($this->frequency);
        }
        
        if ($this->count() < 2) {
            return 1.0;
        }
        
        return (float)$this->count() / 1.0;
    }
    
    /**
     * Maps frequency strings to periods per year.
     * 
     * Supports various frequency notations:
     * - 'D'/'daily': 252 trading days
     * - 'W'/'weekly': 52 weeks
     * - 'M'/'monthly': 12 months
     * - 'Q'/'quarterly': 4 quarters
     * - 'Y'/'yearly'/'annual': 1 year
     * 
     * @param string $frequency Frequency identifier
     * @return float Periods per year
     */
    private function getPeriodsFromFrequency(string $frequency): float
    {
        $frequencyMap = [
            'D' => 252.0,
            'W' => 52.0,
            'M' => 12.0,
            'Q' => 4.0,
            'Y' => 1.0,
            'daily' => 252.0,
            'weekly' => 52.0,
            'monthly' => 12.0,
            'quarterly' => 4.0,
            'yearly' => 1.0,
            'annual' => 1.0,
        ];
        
        return $frequencyMap[strtolower($frequency)] ?? 252.0;
    }
    
    /**
     * Exports the time series as an associative array.
     * 
     * Useful for serialization or data transfer.
     * 
     * @return array Array with 'data', 'index', and 'frequency' keys
     */
    public function toArray(): array
    {
        return [
            'data' => $this->data,
            'index' => $this->index,
            'frequency' => $this->frequency,
        ];
    }
}