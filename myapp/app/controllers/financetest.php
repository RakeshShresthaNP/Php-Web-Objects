<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 #
 # Redistributions must retain the above copyright notice.
 */
use Finance\Returns\TSeries;
use Finance\Options\BlackScholes;
use Finance\Statistics\OLS;
use Finance\General\Utils;
use Finance\Datasets\DataFetcher;

final class cFinanceTest extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        echo "<pre>";
        
        echo "=== PHP Finance Library Examples ===\n\n";

        // Example 1: Time Series Analysis
        echo "1. Time Series Analysis\n";
        echo "----------------------\n";

        $returns = [
            0.01,
            - 0.02,
            0.03,
            - 0.01,
            0.02,
            0.015,
            - 0.005,
            0.025,
            - 0.01,
            0.008
        ];
        $ts = new TSeries($returns, frequency: 'D');

        echo "Sample returns: " . implode(', ', array_map(fn ($x) => number_format($x, 3), $returns)) . "\n";
        echo "Count: " . $ts->count() . "\n";
        echo "Mean: " . number_format($ts->mean(), 4) . "\n";
        echo "Standard Deviation: " . number_format($ts->std(), 4) . "\n";
        echo "Annualized Return: " . number_format($ts->anlzdRet() * 100, 2) . "%\n";
        echo "Sharpe Ratio (2% RF): " . number_format($ts->sharpeRatio(0.02), 3) . "\n";
        echo "Maximum Drawdown: " . number_format($ts->maxDrawdown() * 100, 2) . "%\n";
        echo "Cumulative Return: " . number_format($ts->cumRet() * 100, 2) . "%\n\n";

        // Example 2: Options Pricing
        echo "2. Black-Scholes Options Pricing\n";
        echo "--------------------------------\n";

        $bs = new BlackScholes(spotPrice: 100, strikePrice: 110, timeToExpiry: 0.25, // 3 months
        riskFreeRate: 0.05, volatility: 0.25, dividendYield: 0.02);

        echo "Stock Price: $100\n";
        echo "Strike Price: $110\n";
        echo "Time to Expiry: 3 months\n";
        echo "Risk-Free Rate: 5%\n";
        echo "Volatility: 25%\n";
        echo "Dividend Yield: 2%\n\n";

        echo "Call Price: $" . number_format($bs->callPrice(), 2) . "\n";
        echo "Put Price: $" . number_format($bs->putPrice(), 2) . "\n";
        echo "Call Delta: " . number_format($bs->callDelta(), 3) . "\n";
        echo "Put Delta: " . number_format($bs->putDelta(), 3) . "\n";
        echo "Gamma: " . number_format($bs->gamma(), 4) . "\n";
        echo "Vega: " . number_format($bs->vega(), 3) . "\n";
        echo "Call Theta: " . number_format($bs->callTheta(), 4) . "\n";
        echo "Call Rho: " . number_format($bs->callRho(), 4) . "\n\n";

        // Example 3: Regression Analysis
        echo "3. Linear Regression (CAPM Beta)\n";
        echo "-------------------------------\n";

        // Simulating monthly returns for an asset vs market
        $assetReturns = [
            0.05,
            0.03,
            0.07,
            0.02,
            0.04,
            0.06,
            0.01,
            - 0.02,
            0.08,
            0.03
        ];
        $marketReturns = [
            [
                0.04
            ],
            [
                0.02
            ],
            [
                0.06
            ],
            [
                0.01
            ],
            [
                0.03
            ],
            [
                0.05
            ],
            [
                0.02
            ],
            [
                - 0.01
            ],
            [
                0.07
            ],
            [
                0.025
            ]
        ];

        $ols = new OLS($assetReturns, $marketReturns);
        $coefficients = $ols->getCoefficients();

        echo "Asset vs Market Returns Regression:\n";
        echo "Alpha: " . number_format($coefficients[0], 4) . "\n";
        echo "Beta: " . number_format($coefficients[1], 3) . "\n";
        echo "R-squared: " . number_format($ols->getRSquared(), 3) . "\n";
        echo "Adjusted R-squared: " . number_format($ols->getAdjustedRSquared(), 3) . "\n\n";

        // Example 4: Risk Metrics
        echo "4. Risk and Performance Metrics\n";
        echo "------------------------------\n";

        $monthlyReturns = [
            - 0.05,
            0.02,
            0.04,
            - 0.01,
            0.03,
            - 0.02,
            0.06,
            0.01,
            - 0.03,
            0.04,
            0.02,
            - 0.01
        ];

        echo "Monthly Returns Sample: " . implode(', ', array_map(fn ($x) => number_format($x, 3), $monthlyReturns)) . "\n";
        echo "Value at Risk (95%): " . number_format(Utils::valueAtRisk($monthlyReturns, 0.95) * 100, 2) . "%\n";
        echo "Conditional VaR (95%): " . number_format(Utils::conditionalValueAtRisk($monthlyReturns, 0.95) * 100, 2) . "%\n";
        echo "Compound Return: " . number_format(Utils::compoundReturn($monthlyReturns) * 100, 2) . "%\n";
        echo "Annualized Return: " . number_format(Utils::annualizedReturn($monthlyReturns, 12) * 100, 2) . "%\n";
        echo "Annualized Volatility: " . number_format(Utils::annualizedVolatility($monthlyReturns, 12) * 100, 2) . "%\n\n";

        // Example 5: Portfolio Analytics
        echo "5. Portfolio Analytics\n";
        echo "---------------------\n";

        $portfolio = [
            'AAPL' => 0.30,
            'GOOGL' => 0.25,
            'MSFT' => 0.20,
            'AMZN' => 0.15,
            'TSLA' => 0.10
        ];

        $benchmark = [
            'AAPL' => 0.25,
            'GOOGL' => 0.20,
            'MSFT' => 0.25,
            'AMZN' => 0.20,
            'TSLA' => 0.05,
            'META' => 0.05
        ];

        echo "Portfolio Weights:\n";
        foreach ($portfolio as $stock => $weight) {
            echo "  $stock: " . number_format($weight * 100, 1) . "%\n";
        }

        echo "\nActive Share vs Benchmark: " . number_format(Utils::activeShare($portfolio, $benchmark) * 100, 2) . "%\n";

        // Example 6: Correlation Analysis
        echo "\n6. Correlation Analysis\n";
        echo "----------------------\n";

        $stock1Returns = [
            0.02,
            - 0.01,
            0.03,
            0.01,
            - 0.02,
            0.04,
            - 0.01
        ];
        $stock2Returns = [
            0.015,
            - 0.005,
            0.025,
            0.005,
            - 0.015,
            0.035,
            - 0.01
        ];
        $marketReturnsFlat = [
            0.018,
            - 0.008,
            0.028,
            0.008,
            - 0.018,
            0.038,
            - 0.008
        ];

        echo "Stock 1 vs Stock 2 Correlation: " . number_format(Utils::correlation($stock1Returns, $stock2Returns), 3) . "\n";
        echo "Stock 1 vs Market Correlation: " . number_format(Utils::correlation($stock1Returns, $marketReturnsFlat), 3) . "\n";
        echo "Stock 2 vs Market Correlation: " . number_format(Utils::correlation($stock2Returns, $marketReturnsFlat), 3) . "\n";

        echo "\n=== Examples Complete ===\n";
        
        echo "</pre>";
        
    }
}
