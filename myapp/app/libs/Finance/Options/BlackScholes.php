<?php

declare(strict_types=1);

namespace Finance\Options;

use InvalidArgumentException;
use MathPHP\Probability\Distribution\Continuous\StandardNormal;

/**
 * Black-Scholes option pricing model implementation.
 * 
 * The Black-Scholes model is the foundational framework for pricing European
 * options and calculating their sensitivities (Greeks). It assumes:
 * - No arbitrage opportunities exist
 * - Markets are efficient and liquid
 * - The underlying follows a geometric Brownian motion
 * - Constant risk-free rate and volatility
 * - No transaction costs or taxes
 * - Options can only be exercised at expiration (European style)
 * 
 * The model calculates:
 * - Option prices (calls and puts)
 * - The Greeks: Delta, Gamma, Vega, Theta, and Rho
 * - Implied volatility from market prices
 * 
 * Supports dividend-paying assets through continuous dividend yield adjustment.
 */
class BlackScholes
{
    /**
     * Constructs a Black-Scholes option pricing model.
     * 
     * @param float $spotPrice Current price of the underlying asset
     * @param float $strikePrice Exercise price of the option
     * @param float $timeToExpiry Time to expiration in years (e.g., 0.25 for 3 months)
     * @param float $riskFreeRate Annual risk-free interest rate (e.g., 0.05 for 5%)
     * @param float $volatility Annual volatility (standard deviation of returns, e.g., 0.3 for 30%)
     * @param float|null $dividendYield Annual dividend yield (e.g., 0.02 for 2%, null defaults to 0)
     * @throws InvalidArgumentException if prices are non-positive or parameters are invalid
     */
    public function __construct(
        private float $spotPrice,
        private float $strikePrice,
        private float $timeToExpiry,
        private float $riskFreeRate,
        private float $volatility,
        private ?float $dividendYield = null
    ) {
        if ($spotPrice <= 0 || $strikePrice <= 0) {
            throw new InvalidArgumentException('Spot price and strike price must be positive');
        }
        
        if ($timeToExpiry < 0) {
            throw new InvalidArgumentException('Time to expiry cannot be negative');
        }
        
        if ($volatility < 0) {
            throw new InvalidArgumentException('Volatility cannot be negative');
        }
        
        $this->dividendYield = $dividendYield ?? 0.0;
    }
    
    /**
     * Calculates the d1 parameter in the Black-Scholes formula.
     * 
     * d1 represents the standardized distance between the current price
     * and strike, adjusted for drift and volatility. It's used in
     * calculating N(d1) which gives the delta for calls.
     * 
     * Formula: d1 = [ln(S/K) + (r - q + σ²/2)T] / (σ√T)
     * 
     * @return float The d1 value
     */
    private function d1(): float
    {
        if ($this->timeToExpiry == 0) {
            return 0.0;
        }
        
        if ($this->volatility == 0) {
            if ($this->spotPrice > $this->strikePrice) {
                return PHP_FLOAT_MAX;
            } elseif ($this->spotPrice < $this->strikePrice) {
                return -PHP_FLOAT_MAX;
            } else {
                return 0.0;
            }
        }
        
        $numerator = log($this->spotPrice / $this->strikePrice) + 
                    ($this->riskFreeRate - $this->dividendYield + 0.5 * pow($this->volatility, 2)) * $this->timeToExpiry;
        $denominator = $this->volatility * sqrt($this->timeToExpiry);
        
        return $numerator / $denominator;
    }
    
    /**
     * Calculates the d2 parameter in the Black-Scholes formula.
     * 
     * d2 represents the probability that the option will be exercised
     * at expiration. N(d2) gives the risk-neutral probability of
     * the option finishing in-the-money.
     * 
     * Formula: d2 = d1 - σ√T
     * 
     * @return float The d2 value
     */
    private function d2(): float
    {
        return $this->d1() - $this->volatility * sqrt($this->timeToExpiry);
    }
    
    /**
     * Calculates the cumulative distribution function of standard normal.
     * 
     * @param float $x Input value
     * @return float Cumulative probability N(x)
     */
    private function normCdf(float $x): float
    {
        $normal = new StandardNormal();
        return $normal->cdf($x);
    }
    
    /**
     * Calculates the probability density function of standard normal.
     * 
     * @param float $x Input value
     * @return float Probability density n(x)
     */
    private function normPdf(float $x): float
    {
        return exp(-0.5 * pow($x, 2)) / sqrt(2 * M_PI);
    }
    
    /**
     * Calculates the theoretical price of a European call option.
     * 
     * The Black-Scholes call formula:
     * C = S₀e^(-qT)N(d1) - Ke^(-rT)N(d2)
     * 
     * Where:
     * - S₀ is the current spot price
     * - K is the strike price
     * - r is the risk-free rate
     * - q is the dividend yield
     * - T is time to expiration
     * - N() is the cumulative standard normal distribution
     * 
     * @return float Call option price
     */
    public function callPrice(): float
    {
        if ($this->timeToExpiry == 0) {
            return max(0, $this->spotPrice - $this->strikePrice);
        }
        
        $d1 = $this->d1();
        $d2 = $this->d2();
        
        $discountFactor = exp(-$this->riskFreeRate * $this->timeToExpiry);
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        
        return $this->spotPrice * $dividendDiscount * $this->normCdf($d1) - 
               $this->strikePrice * $discountFactor * $this->normCdf($d2);
    }
    
    /**
     * Calculates the theoretical price of a European put option.
     * 
     * The Black-Scholes put formula:
     * P = Ke^(-rT)N(-d2) - S₀e^(-qT)N(-d1)
     * 
     * Uses put-call parity relationship for consistency.
     * 
     * @return float Put option price
     */
    public function putPrice(): float
    {
        if ($this->timeToExpiry == 0) {
            return max(0, $this->strikePrice - $this->spotPrice);
        }
        
        $d1 = $this->d1();
        $d2 = $this->d2();
        
        $discountFactor = exp(-$this->riskFreeRate * $this->timeToExpiry);
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        
        return $this->strikePrice * $discountFactor * $this->normCdf(-$d2) - 
               $this->spotPrice * $dividendDiscount * $this->normCdf(-$d1);
    }
    
    /**
     * Calculates Delta for a call option.
     * 
     * Delta measures the rate of change of option price with respect
     * to the underlying price. For calls, delta ranges from 0 to 1.
     * It also represents the hedge ratio and the probability of
     * finishing in-the-money under certain assumptions.
     * 
     * Formula: Δ_call = e^(-qT)N(d1)
     * 
     * @return float Call delta (0 to 1)
     */
    public function callDelta(): float
    {
        if ($this->timeToExpiry == 0) {
            return $this->spotPrice > $this->strikePrice ? 1.0 : 0.0;
        }
        
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        return $dividendDiscount * $this->normCdf($this->d1());
    }
    
    /**
     * Calculates Delta for a put option.
     * 
     * Put delta ranges from -1 to 0, representing the negative
     * relationship between put value and underlying price.
     * 
     * Formula: Δ_put = -e^(-qT)N(-d1)
     * 
     * @return float Put delta (-1 to 0)
     */
    public function putDelta(): float
    {
        if ($this->timeToExpiry == 0) {
            return $this->spotPrice < $this->strikePrice ? -1.0 : 0.0;
        }
        
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        return -$dividendDiscount * $this->normCdf(-$this->d1());
    }
    
    /**
     * Calculates Gamma for both call and put options.
     * 
     * Gamma measures the rate of change of delta with respect to
     * the underlying price. It indicates how much the delta will
     * change for a $1 move in the underlying. Gamma is highest
     * for at-the-money options near expiration.
     * 
     * Formula: Γ = e^(-qT)n(d1) / (S₀σ√T)
     * 
     * @return float Gamma (same for calls and puts)
     */
    public function gamma(): float
    {
        if ($this->timeToExpiry == 0 || $this->volatility == 0) {
            return 0.0;
        }
        
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        $denominator = $this->spotPrice * $this->volatility * sqrt($this->timeToExpiry);
        
        if ($denominator == 0) {
            return 0.0;
        }
        
        return $dividendDiscount * $this->normPdf($this->d1()) / $denominator;
    }
    
    /**
     * Calculates Vega for both call and put options.
     * 
     * Vega measures sensitivity to volatility changes. It represents
     * the change in option price for a 1% change in implied volatility.
     * Vega is highest for at-the-money options and decreases as
     * expiration approaches.
     * 
     * Formula: ν = S₀e^(-qT)n(d1)√T / 100
     * 
     * @return float Vega (price change per 1% volatility change)
     */
    public function vega(): float
    {
        if ($this->timeToExpiry == 0) {
            return 0.0;
        }
        
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        return $this->spotPrice * $dividendDiscount * $this->normPdf($this->d1()) * sqrt($this->timeToExpiry) / 100;
    }
    
    /**
     * Calculates Theta for a call option.
     * 
     * Theta measures time decay - the rate at which option value
     * decreases as expiration approaches. Usually expressed as
     * daily decay (divided by 365). Theta is typically negative
     * for long options, representing value lost each day.
     * 
     * @return float Call theta (daily time decay)
     */
    public function callTheta(): float
    {
        if ($this->timeToExpiry == 0) {
            return 0.0;
        }
        
        $d1 = $this->d1();
        $d2 = $this->d2();
        
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        $discountFactor = exp(-$this->riskFreeRate * $this->timeToExpiry);
        
        $term1 = -$this->spotPrice * $dividendDiscount * $this->normPdf($d1) * $this->volatility / 
                 (2 * sqrt($this->timeToExpiry));
        $term2 = $this->riskFreeRate * $this->strikePrice * $discountFactor * $this->normCdf($d2);
        $term3 = $this->dividendYield * $this->spotPrice * $dividendDiscount * $this->normCdf($d1);
        
        return ($term1 - $term2 + $term3) / 365;
    }
    
    /**
     * Calculates Theta for a put option.
     * 
     * Put theta has similar interpretation to call theta but
     * with adjustments for put-specific terms.
     * 
     * @return float Put theta (daily time decay)
     */
    public function putTheta(): float
    {
        if ($this->timeToExpiry == 0) {
            return 0.0;
        }
        
        $d1 = $this->d1();
        $d2 = $this->d2();
        
        $dividendDiscount = exp(-$this->dividendYield * $this->timeToExpiry);
        $discountFactor = exp(-$this->riskFreeRate * $this->timeToExpiry);
        
        $term1 = -$this->spotPrice * $dividendDiscount * $this->normPdf($d1) * $this->volatility / 
                 (2 * sqrt($this->timeToExpiry));
        $term2 = $this->riskFreeRate * $this->strikePrice * $discountFactor * $this->normCdf(-$d2);
        $term3 = $this->dividendYield * $this->spotPrice * $dividendDiscount * $this->normCdf(-$d1);
        
        return ($term1 + $term2 - $term3) / 365;
    }
    
    /**
     * Calculates Rho for a call option.
     * 
     * Rho measures sensitivity to interest rate changes.
     * It represents the change in option price for a 1% change
     * in the risk-free rate. Rho is more significant for
     * longer-dated options.
     * 
     * Formula: ρ_call = KTe^(-rT)N(d2) / 100
     * 
     * @return float Call rho (price change per 1% rate change)
     */
    public function callRho(): float
    {
        if ($this->timeToExpiry == 0) {
            return 0.0;
        }
        
        $d2 = $this->d2();
        $discountFactor = exp(-$this->riskFreeRate * $this->timeToExpiry);
        
        return $this->strikePrice * $this->timeToExpiry * $discountFactor * $this->normCdf($d2) / 100;
    }
    
    /**
     * Calculates Rho for a put option.
     * 
     * Put rho is typically negative, as higher interest rates
     * generally decrease put values.
     * 
     * Formula: ρ_put = -KTe^(-rT)N(-d2) / 100
     * 
     * @return float Put rho (price change per 1% rate change)
     */
    public function putRho(): float
    {
        if ($this->timeToExpiry == 0) {
            return 0.0;
        }
        
        $d2 = $this->d2();
        $discountFactor = exp(-$this->riskFreeRate * $this->timeToExpiry);
        
        return -$this->strikePrice * $this->timeToExpiry * $discountFactor * $this->normCdf(-$d2) / 100;
    }
    
    /**
     * Calculates implied volatility from market option price.
     * 
     * Uses Newton-Raphson method to iteratively solve for the volatility
     * that produces the observed market price. Implied volatility is
     * the market's expectation of future volatility embedded in option prices.
     * 
     * @param float $marketPrice Observed market price of the option
     * @param string $optionType 'call' or 'put'
     * @param float $tolerance Convergence tolerance for price matching
     * @param int $maxIterations Maximum iterations for convergence
     * @return float Implied volatility (annualized)
     * @throws InvalidArgumentException if option type is invalid
     */
    public function impliedVolatility(
        float $marketPrice,
        string $optionType = 'call',
        float $tolerance = 1e-6,
        int $maxIterations = 100
    ): float {
        if (!in_array($optionType, ['call', 'put'])) {
            throw new InvalidArgumentException('Option type must be "call" or "put"');
        }
        
        $vol = 0.2;
        
        for ($i = 0; $i < $maxIterations; $i++) {
            $this->volatility = $vol;
            
            if ($optionType === 'call') {
                $price = $this->callPrice();
            } else {
                $price = $this->putPrice();
            }
            
            $vega = $this->vega() * 100;
            
            if (abs($price - $marketPrice) < $tolerance) {
                return $vol;
            }
            
            if ($vega == 0) {
                break;
            }
            
            $vol = $vol - ($price - $marketPrice) / $vega;
            
            if ($vol <= 0) {
                $vol = 0.001;
            }
        }
        
        return $vol;
    }
    
    /**
     * Returns all Greeks for the specified option type.
     * 
     * The Greeks are partial derivatives that measure different
     * sensitivities of the option price:
     * - Delta: Price sensitivity to underlying moves
     * - Gamma: Delta sensitivity to underlying moves
     * - Vega: Price sensitivity to volatility changes
     * - Theta: Price sensitivity to time decay
     * - Rho: Price sensitivity to interest rate changes
     * 
     * @param string $optionType 'call' or 'put'
     * @return array Associative array of Greeks
     * @throws InvalidArgumentException if option type is invalid
     */
    public function getGreeks(string $optionType = 'call'): array
    {
        if (!in_array($optionType, ['call', 'put'])) {
            throw new InvalidArgumentException('Option type must be "call" or "put"');
        }
        
        return [
            'delta' => $optionType === 'call' ? $this->callDelta() : $this->putDelta(),
            'gamma' => $this->gamma(),
            'vega' => $this->vega(),
            'theta' => $optionType === 'call' ? $this->callTheta() : $this->putTheta(),
            'rho' => $optionType === 'call' ? $this->callRho() : $this->putRho(),
        ];
    }
    
    /**
     * Returns comprehensive summary of option pricing and Greeks.
     * 
     * Provides all input parameters, calculated price, and complete
     * Greeks in a single array for analysis and reporting.
     * 
     * @param string $optionType 'call' or 'put'
     * @return array Complete option analysis summary
     * @throws InvalidArgumentException if option type is invalid
     */
    public function getSummary(string $optionType = 'call'): array
    {
        if (!in_array($optionType, ['call', 'put'])) {
            throw new InvalidArgumentException('Option type must be "call" or "put"');
        }
        
        $price = $optionType === 'call' ? $this->callPrice() : $this->putPrice();
        $greeks = $this->getGreeks($optionType);
        
        return [
            'type' => $optionType,
            'spot_price' => $this->spotPrice,
            'strike_price' => $this->strikePrice,
            'time_to_expiry' => $this->timeToExpiry,
            'risk_free_rate' => $this->riskFreeRate,
            'volatility' => $this->volatility,
            'dividend_yield' => $this->dividendYield,
            'price' => $price,
            'greeks' => $greeks,
        ];
    }
}