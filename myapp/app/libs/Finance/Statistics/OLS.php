<?php

declare(strict_types=1);

namespace Finance\Statistics;

use InvalidArgumentException;
use MathPHP\LinearAlgebra\Matrix;
use MathPHP\LinearAlgebra\MatrixFactory;
use MathPHP\Probability\Distribution\Continuous\StudentT;
use MathPHP\Probability\Distribution\Continuous\F;

/**
 * Ordinary Least Squares (OLS) regression implementation.
 * 
 * This class performs linear regression using the ordinary least squares method,
 * which minimizes the sum of squared residuals between observed and predicted values.
 * It supports both simple (single predictor) and multiple regression, with or without
 * an intercept term.
 * 
 * The class calculates:
 * - Regression coefficients (beta estimates)
 * - Fitted values and residuals
 * - R-squared and adjusted R-squared
 * - Standard errors, t-statistics, and p-values for coefficients
 * - F-test for overall model significance
 */
class OLS
{
    private array $y;
    private array $X;
    private ?array $coefficients = null;
    private ?array $residuals = null;
    private ?array $fittedValues = null;
    private ?float $rSquared = null;
    private ?float $adjustedRSquared = null;
    private ?array $standardErrors = null;
    private ?array $tStatistics = null;
    private ?array $pValues = null;
    private int $n;
    private int $k;
    
    /**
     * Constructs an OLS regression model.
     * 
     * @param array $y Dependent variable (response) values
     * @param array $X Independent variables (predictors). Can be 1D array for simple regression
     *                 or 2D array for multiple regression
     * @param bool $hasIntercept Whether to include an intercept term (default: true)
     * @throws InvalidArgumentException if inputs are invalid or insufficient observations
     */
    public function __construct(
        array $y, 
        array $X, 
        private bool $hasIntercept = true
    ) {
        $this->validateInput($y, $X);
        
        $this->y = array_values($y);
        
        if ($this->hasIntercept) {
            $this->X = $this->addIntercept($X);
        } else {
            $this->X = $this->normalizeX($X);
        }
        
        $this->n = count($y);
        $this->k = count($this->X[0]);
        
        if ($this->n <= $this->k) {
            throw new InvalidArgumentException('Number of observations must be greater than number of parameters');
        }
        
        $this->fit();
    }
    
    /**
     * Validates input data for regression.
     * 
     * Ensures:
     * - Arrays are non-empty
     * - X and y have same number of observations
     * - X rows have consistent dimensions
     * - X contains numeric data
     * 
     * @throws InvalidArgumentException if validation fails
     */
    private function validateInput(array $y, array $X): void
    {
        if (empty($y) || empty($X)) {
            throw new InvalidArgumentException('Input arrays cannot be empty');
        }
        
        $n = count($y);
        if (count($X) !== $n) {
            throw new InvalidArgumentException('X and y must have the same number of observations');
        }
        
        $firstRowLength = is_array($X[0]) ? count($X[0]) : 1;
        foreach ($X as $row) {
            if (!is_array($row) && !is_numeric($row)) {
                throw new InvalidArgumentException('X must be a 2D array or 1D array of numbers');
            }
            if (is_array($row) && count($row) !== $firstRowLength) {
                throw new InvalidArgumentException('All rows in X must have the same number of columns');
            }
        }
    }

    /**
     * Ensures input data X is formatted as a consistent 2D array.
     * Converts 1D arrays (single predictor) into 2D arrays by wrapping each value.
     * Reindexes array values to ensure sequential numeric keys.
     */
    private function normalizeX(array $X): array
    {
        $normalized = [];
        
        foreach ($X as $row) {
            if (is_array($row)) {
                $normalized[] = array_values($row);
            } else {
                $normalized[] = [$row];
            }
        }
        
        return $normalized;
    }
    
    /**
     * Adds an intercept column (column of 1s) as the first column of the X matrix.
     * This allows the OLS model to estimate a y-intercept (bias term).
     * Handles both 1D and 2D input arrays.
     */
    private function addIntercept(array $X): array
    {
        $withIntercept = [];
        
        foreach ($X as $row) {
            if (is_array($row)) {
                $withIntercept[] = array_merge([1.0], array_values($row));
            } else {
                $withIntercept[] = [1.0, $row];
            }
        }
        
        return $withIntercept;
    }
    
    /**
     * Fits the OLS model using matrix algebra.
     * 
     * Calculates coefficients using the normal equation:
     * β = (X'X)^(-1) X'y
     * 
     * Then computes all model statistics including fitted values,
     * residuals, R-squared, standard errors, and significance tests.
     */
    private function fit(): void
    {
        $XMatrix = MatrixFactory::create($this->X);
        $yVector = MatrixFactory::createFromColumnVector($this->y);

        /** @var \MathPHP\LinearAlgebra\NumericMatrix $XtX */
        $XtX = $XMatrix->transpose()->multiply($XMatrix);
        $XtXInverse = $XtX->inverse();
        $Xty = $XMatrix->transpose()->multiply($yVector);
        
        $betaMatrix = $XtXInverse->multiply($Xty);
        $this->coefficients = $betaMatrix->getColumn(0);
        
        $this->calculateFittedValues();
        $this->calculateResiduals();
        $this->calculateRSquared();
        $this->calculateStandardErrors($XtXInverse);
        $this->calculateTStatistics();
        $this->calculatePValues();
    }
    
    /**
     * Calculates predicted y values for each observation.
     * 
     * Fitted values are computed as: ŷ = Xβ
     * where β are the estimated coefficients.
     */
    private function calculateFittedValues(): void
    {
        $this->fittedValues = [];
        
        foreach ($this->X as $row) {
            $fitted = 0.0;
            for ($i = 0; $i < count($row); $i++) {
                $fitted += $row[$i] * $this->coefficients[$i];
            }
            $this->fittedValues[] = $fitted;
        }
    }
    
    /**
     * Calculates residuals (errors) for each observation.
     * 
     * Residuals are the differences between observed and fitted values:
     * e = y - ŷ
     */
    private function calculateResiduals(): void
    {
        $this->residuals = [];
        
        for ($i = 0; $i < $this->n; $i++) {
            $this->residuals[] = $this->y[$i] - $this->fittedValues[$i];
        }
    }
    
    /**
     * Calculates R-squared and adjusted R-squared.
     * 
     * R-squared measures the proportion of variance explained by the model:
     * R² = 1 - (SS_res / SS_tot)
     * 
     * Adjusted R-squared accounts for the number of predictors:
     * R²_adj = 1 - [(1 - R²) * (n - 1) / (n - k)]
     */
    private function calculateRSquared(): void
    {
        $yMean = array_sum($this->y) / $this->n;
        
        $ssTot = 0.0;
        $ssRes = 0.0;
        
        for ($i = 0; $i < $this->n; $i++) {
            $ssTot += pow($this->y[$i] - $yMean, 2);
            $ssRes += pow($this->residuals[$i], 2);
        }
        
        $this->rSquared = 1 - ($ssRes / $ssTot);
        
        $this->adjustedRSquared = 1 - ((1 - $this->rSquared) * ($this->n - 1) / ($this->n - $this->k));
    }
    
    /**
     * Calculates standard errors for coefficient estimates.
     * 
     * Standard errors are derived from the diagonal of the covariance matrix:
     * SE(β) = sqrt(diag(σ² * (X'X)^(-1)))
     * where σ² is the residual variance.
     * 
     * @param Matrix|\MathPHP\LinearAlgebra\NumericMatrix $XtXInverse The inverse of X'X matrix
     */
    private function calculateStandardErrors(Matrix $XtXInverse): void
    {
        $ssRes = array_sum(array_map(fn($r) => pow($r, 2), $this->residuals));
        $sigma2 = $ssRes / ($this->n - $this->k);
        
        $this->standardErrors = [];
        $covarianceMatrix = $XtXInverse->scalarMultiply($sigma2);
        
        for ($i = 0; $i < $this->k; $i++) {
            $this->standardErrors[] = sqrt($covarianceMatrix->get($i, $i));
        }
    }
    
    /**
     * Calculates t-statistics for hypothesis testing.
     * 
     * Tests the null hypothesis that each coefficient equals zero:
     * t = β / SE(β)
     * 
     * Used to determine statistical significance of predictors.
     */
    private function calculateTStatistics(): void
    {
        $this->tStatistics = [];
        
        for ($i = 0; $i < $this->k; $i++) {
            if ($this->standardErrors[$i] != 0) {
                $this->tStatistics[] = $this->coefficients[$i] / $this->standardErrors[$i];
            } else {
                $this->tStatistics[] = 0.0;
            }
        }
    }
    
    /**
     * Calculates p-values for coefficient significance tests.
     * 
     * P-values are computed from t-statistics using the Student's t-distribution
     * with (n - k) degrees of freedom. Two-tailed test is used.
     */
    private function calculatePValues(): void
    {
        $this->pValues = [];
        $df = $this->n - $this->k;
        $tDist = new StudentT($df);
        
        foreach ($this->tStatistics as $tStat) {
            $this->pValues[] = 2 * (1 - $tDist->cdf(abs($tStat)));
        }
    }
    
    /**
     * Returns the estimated regression coefficients.
     * @return array Coefficient estimates (β values)
     */
    public function getCoefficients(): array
    {
        return $this->coefficients;
    }
    
    /**
     * Returns the residuals (prediction errors).
     * @return array Residual values for each observation
     */
    public function getResiduals(): array
    {
        return $this->residuals;
    }
    
    /**
     * Returns the fitted (predicted) values.
     * @return array Predicted y values for each observation
     */
    public function getFittedValues(): array
    {
        return $this->fittedValues;
    }
    
    /**
     * Returns the coefficient of determination.
     * @return float R-squared value (0 to 1)
     */
    public function getRSquared(): float
    {
        return $this->rSquared;
    }
    
    /**
     * Returns the adjusted R-squared.
     * @return float Adjusted R-squared value
     */
    public function getAdjustedRSquared(): float
    {
        return $this->adjustedRSquared;
    }
    
    /**
     * Returns standard errors of coefficient estimates.
     * @return array Standard error for each coefficient
     */
    public function getStandardErrors(): array
    {
        return $this->standardErrors;
    }
    
    /**
     * Returns t-statistics for coefficient significance.
     * @return array T-statistic for each coefficient
     */
    public function getTStatistics(): array
    {
        return $this->tStatistics;
    }
    
    /**
     * Returns p-values for coefficient significance tests.
     * @return array P-value for each coefficient
     */
    public function getPValues(): array
    {
        return $this->pValues;
    }
    
    /**
     * Makes predictions for new observations.
     * 
     * @param array $X New predictor values (single observation or multiple)
     * @return array Predicted y values
     * @throws InvalidArgumentException if input dimensions don't match training data
     */
    public function predict(array $X): array
    {
        if (!is_array($X[0])) {
            $X = [$X];
        }
        
        $predictions = [];
        
        foreach ($X as $row) {
            if ($this->hasIntercept) {
                $row = array_merge([1.0], $row);
            }
            
            if (count($row) !== $this->k) {
                throw new InvalidArgumentException('Prediction input must have the same number of features as training data');
            }
            
            $prediction = 0.0;
            for ($i = 0; $i < $this->k; $i++) {
                $prediction += $row[$i] * $this->coefficients[$i];
            }
            $predictions[] = $prediction;
        }
        
        return count($predictions) === 1 ? [$predictions[0]] : $predictions;
    }
    
    /**
     * Returns a comprehensive summary of regression results.
     * 
     * Includes model fit statistics and coefficient estimates with
     * their standard errors, t-statistics, and p-values.
     * 
     * @return array Associative array with full regression summary
     */
    public function getSummary(): array
    {
        $summary = [
            'n_observations' => $this->n,
            'n_parameters' => $this->k,
            'degrees_of_freedom' => $this->n - $this->k,
            'r_squared' => $this->rSquared,
            'adjusted_r_squared' => $this->adjustedRSquared,
            'coefficients' => [],
        ];
        
        $labels = $this->getCoefficientLabels();
        
        for ($i = 0; $i < $this->k; $i++) {
            $summary['coefficients'][$labels[$i]] = [
                'estimate' => $this->coefficients[$i],
                'std_error' => $this->standardErrors[$i],
                't_statistic' => $this->tStatistics[$i],
                'p_value' => $this->pValues[$i],
            ];
        }
        
        return $summary;
    }
    
    /**
     * Generates labels for coefficients.
     * 
     * Returns 'intercept' for the first coefficient if model has intercept,
     * otherwise labels are 'x1', 'x2', etc.
     * 
     * @return array Coefficient labels
     */
    private function getCoefficientLabels(): array
    {
        $labels = [];
        
        if ($this->hasIntercept) {
            $labels[] = 'intercept';
            for ($i = 1; $i < $this->k; $i++) {
                $labels[] = 'x' . $i;
            }
        } else {
            for ($i = 0; $i < $this->k; $i++) {
                $labels[] = 'x' . ($i + 1);
            }
        }
        
        return $labels;
    }
    
    /**
     * Performs F-test for overall model significance.
     * 
     * Tests the null hypothesis that all slope coefficients are zero
     * (model has no explanatory power).
     * 
     * @return array F-statistic, p-value, and degrees of freedom
     */
    public function fTest(): array
    {
        $yMean = array_sum($this->y) / $this->n;
        
        $ssReg = 0.0;
        $ssRes = 0.0;
        
        for ($i = 0; $i < $this->n; $i++) {
            $ssReg += pow($this->fittedValues[$i] - $yMean, 2);
            $ssRes += pow($this->residuals[$i], 2);
        }
        
        $dfReg = $this->k - ($this->hasIntercept ? 1 : 0);
        $dfRes = $this->n - $this->k;
        
        if ($dfReg <= 0 || $dfRes <= 0 || $ssRes == 0) {
            return [
                'f_statistic' => null,
                'p_value' => null,
                'df_regression' => $dfReg,
                'df_residual' => $dfRes,
            ];
        }
        
        $msReg = $ssReg / $dfReg;
        $msRes = $ssRes / $dfRes;
        
        $fStatistic = $msReg / $msRes;
        
        $fDist = new F($dfReg, $dfRes);
        $pValue = 1 - $fDist->cdf($fStatistic);
        
        return [
            'f_statistic' => $fStatistic,
            'p_value' => $pValue,
            'df_regression' => $dfReg,
            'df_residual' => $dfRes,
        ];
    }
}