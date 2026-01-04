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
use MathPHP\Finance;

final class cMath extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // Financial payment for a loan or annuity with compound interest
        $rate = 0.035 / 12; // 3.5% interest paid at the end of every month
        $periods = 30 * 12; // 30-year mortgage
        $present_value = 265000; // Mortgage note of $265,000.00
        $future_value = 0;
        $beginning = false; // Adjust the payment to the beginning or end of the period
        $payment = 1189.97;

        $mdata['pmt'] = Finance::pmt($rate, $periods, $present_value, $future_value, $beginning);

        // Interest on a financial payment for a loan or annuity with compound interest.
        $period = 1; // First payment period
        $mdata['ipmt'] = Finance::ipmt($rate, $period, $periods, $present_value, $future_value, $beginning);

        // Principle on a financial payment for a loan or annuity with compound interest
        $mdata['ppmt'] = Finance::ppmt($rate, $period, $periods, $present_value, $future_value, $beginning);

        // Number of payment periods of an annuity.
        $mdata['periods'] = Finance::periods($rate, $payment, $present_value, $future_value, $beginning);

        // Annual Equivalent Rate (AER) of an annual percentage rate (APR)
        $nominal = 0.035; // APR 3.5% interest
        $periods = 12; // Compounded monthly
        $mdata['aer'] = Finance::aer($nominal, $periods);

        // Annual nominal rate of an annual effective rate (AER)
        $mdata['nomial'] = Finance::nominal($mdata['aer'], $periods);

        // Future value for a loan or annuity with compound interest
        $mdata['fv'] = Finance::fv($rate, $periods, $payment, $present_value, $beginning);

        // Present value for a loan or annuity with compound interest
        $mdata['pv'] = Finance::pv($rate, $periods, $payment, $future_value, $beginning);

        // Net present value of cash flows
        $values = [
            - 1000,
            100,
            200,
            300,
            400
        ];
        $mdata['npv'] = Finance::npv($rate, $values);

        // Internal rate of return
        $values = [
            - 100,
            50,
            40,
            30
        ];
        $mdata['irr'] = Finance::irr($values); // Rate of return of an initial investment of $100 with returns of $50, $40, and $30

        // Modified internal rate of return
        $finance_rate = 0.05; // 5% financing
        $reinvestment_rate = 0.10; // reinvested at 10%
        $mdata['mirr'] = Finance::mirr($values, $finance_rate, $reinvestment_rate); // rate of return of an initial investment of $100 at 5% financing with returns of $50, $40, and $30 reinvested at 10%

        // Discounted payback of an investment
        $values = [
            - 1000,
            100,
            200,
            300,
            400,
            500
        ];
        $rate = 0.1;
        $mdata['payback'] = Finance::payback($values, $rate); // The payback period of an investment with a $1,000 investment and future returns of $100, $200, $300, $400, $500 and a discount rate of 0.10

        // Profitability index
        $values = [
            - 100,
            50,
            50,
            50
        ];
        $mdata['profitability_index'] = Finance::profitabilityIndex($values, $rate); // The profitability index of an initial $100 investment with future returns of $50, $50, $50 with a 10% discount rate

        $data['data'] = $mdata;

        print_r($data);
    }
}
