<?php

/**
 # Copyright Rakesh Shrestha (rakesh.shrestha@gmail.com)
 # All rights reserved.
 #
 # Redistribution and use in source and binary forms, with or without
 # modification, are permitted provided that the following conditions are
 # met:
 $helper = new HelperCreditScore();

 // 1. Get the simple integer
 $score = $helper->calculateFinalScore(0.95, 0.10, 72, 2, 0);

 // 2. Get the full detailed data for your database JSON column
 $fullDetails = $helper->getExplanation();

 echo "User Score: " . $score; 
 // $score is 822 (example)
 // $fullDetails['final_score'] is also 822
 #
 # Redistributions must retain the above copyright notice.
 */
declare(strict_types = 1);

use BcMath\Number;

final class HelperCreditScore
{

    /**
     * Stores the state of the current calculation for later analysis.
     */
    private array $lastBreakdown = [];

    public function __construct(private int $minScore = 300, private int $maxPoints = 550)
    {}

    /**
     * Core Calculation - Returns INT for compatibility with existing code.
     */
    public function calculateFinalScore(float $paymentRate, float $utilizationRate, int $historyMonths, int $uniqueAccountTypes, int $recentInquiries): int
    {
        $max = new Number((string) $this->maxPoints);

        // 1. Precise Factor Calculations using PHP 8.4 BcMath\Number
        $f1 = (new Number((string) $paymentRate))->mul(new Number("0.35"));

        $uVal = (new Number("1.0"))->sub(new Number((string) $utilizationRate));
        $uVal = (float) $uVal->format() < 0 ? new Number("0") : $uVal;
        $f2 = $uVal->mul(new Number("0.30"));

        $f3 = (new Number((string) min($historyMonths / 120, 1.0)))->mul(new Number("0.15"));

        $mVal = new Number($uniqueAccountTypes >= 2 ? "1.0" : "0.5");
        $f4 = $mVal->mul(new Number("0.10"));

        $penalty = (new Number((string) $recentInquiries))->mul(new Number("0.2"));
        $nVal = (new Number("1.0"))->sub($penalty);
        $nVal = (float) $nVal->format() < 0 ? new Number("0") : $nVal;
        $f5 = $nVal->mul(new Number("0.10"));

        // 2. Map factors to raw points
        $pts = [
            'payment_history' => (int) $f1->mul($max)->format(0),
            'utilization' => (int) $f2->mul($max)->format(0),
            'history_length' => (int) $f3->mul($max)->format(0),
            'credit_mix' => (int) $f4->mul($max)->format(0),
            'new_credit' => (int) $f5->mul($max)->format(0)
        ];

        $totalEarned = array_sum($pts);
        $finalScore = $this->minScore + $totalEarned;

        // 3. Save internal state for explanation/comparison methods
        $this->lastBreakdown = [
            'final_score' => $finalScore,
            'breakdown' => $pts,
            'inputs' => [
                'payment_rate' => $paymentRate,
                'utilization_rate' => $utilizationRate,
                'history_months' => $historyMonths,
                'account_types' => $uniqueAccountTypes,
                'inquiries' => $recentInquiries
            ]
        ];

        return $finalScore;
    }

    /**
     * Used to get the full state to save into your JSON database column.
     */
    public function getExplanation(): array
    {
        return $this->lastBreakdown;
    }

    /**
     * COMPARES current calculation against the previous JSON snapshot.
     * This replaces getDetailedComparison and getTrend with a single unified method.
     */
    public function getTrendAnalysis(?string $previousJson): array
    {
        if (empty($this->lastBreakdown)) {
            return [
                'direction' => 'stable',
                'change' => 0,
                'message' => 'No calculation found.'
            ];
        }

        if (! $previousJson) {
            return [
                'direction' => 'stable',
                'change' => 0,
                'message' => 'First score generated. Keep paying on time to build history!'
            ];
        }

        $prevData = json_decode($previousJson, true);
        $current = $this->lastBreakdown['final_score'];
        $previous = (int) ($prevData['final_score'] ?? $this->minScore);
        $diff = $current - $previous;

        // Determine Direction
        $direction = match (true) {
            $diff > 0 => 'up',
            $diff < 0 => 'down',
            default => 'stable'
        };

        // Deep Factor Analysis (The "Why")
        $reasons = [];
        $currB = $this->lastBreakdown['breakdown'];
        $prevB = $prevData['breakdown'] ?? [];

        if (! empty($prevB)) {
            if ($currB['utilization'] < $prevB['utilization']) {
                $reasons[] = "Your credit usage increased, which negatively impacted your score.";
            } elseif ($currB['utilization'] > $prevB['utilization']) {
                $reasons[] = "Lower credit utilization is helping your score rise.";
            }

            if ($currB['payment_history'] > $prevB['payment_history']) {
                $reasons[] = "Great work! On-time payments are boosting your reliability.";
            }

            if ($currB['new_credit'] < $prevB['new_credit']) {
                $reasons[] = "Recent credit inquiries have caused a small temporary dip.";
            }
        }

        $message = match ($direction) {
            'up' => "Your score improved by {$diff} points!",
            'down' => "Your score dropped by " . abs($diff) . " points.",
            'stable' => "Your score is stable at {$current}."
        };

        return [
            'direction' => $direction, // 'up', 'down', 'stable'
            'change' => $diff,
            'current' => $current,
            'previous' => $previous,
            'message' => $message,
            'reasons' => $reasons
        ];
    }
}
