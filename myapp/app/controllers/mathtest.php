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
use MathPHP\NumericalAnalysis\Interpolation;

final class cMathTest extends cController
{

    public function __construct()
    {
        parent::__construct();
    }

    public function index()
    {
        // Interpolation is a method of constructing new data points with the range
        // of a discrete set of known data points.
        // Each integration method can take input in two ways:
        //  1) As a set of points (inputs and outputs of a function)
        //  2) As a callback function, and the number of function evaluations to
        //     perform on an interval between a start and end point.
        
        // Input as a set of points
        $points = [[0, 1], [1, 4], [2, 9], [3, 16]];
        
        // Input as a callback function
        $f⟮x⟯ = function ($x) {
            return $x**2 + 2 * $x + 1;
        };
        [$start, $end, $n] = [0, 3, 4];
        
        // Lagrange Polynomial
        // Returns a function p(x) of x
        $p = Interpolation\LagrangePolynomial::interpolate($points);                // input as a set of points
        $p = Interpolation\LagrangePolynomial::interpolate($f⟮x⟯, $start, $end, $n); // input as a callback function
        
        $p(0); // 1
        $p(3); // 16
        
        // Nevilles Method
        // More accurate than Lagrange Polynomial Interpolation given the same input
        // Returns the evaluation of the interpolating polynomial at the $target point
        $target = 2;
        $result = Interpolation\NevillesMethod::interpolate($target, $points);                // input as a set of points
        $result = Interpolation\NevillesMethod::interpolate($target, $f⟮x⟯, $start, $end, $n); // input as a callback function
        
        // Newton Polynomial (Forward)
        // Returns a function p(x) of x
        $p = Interpolation\NewtonPolynomialForward::interpolate($points);                // input as a set of points
        $p = Interpolation\NewtonPolynomialForward::interpolate($f⟮x⟯, $start, $end, $n); // input as a callback function
        
        $p(0); // 1
        $p(3); // 16
        
        // Natural Cubic Spline
        // Returns a piecewise polynomial p(x)
        $p = Interpolation\NaturalCubicSpline::interpolate($points);                // input as a set of points
        $p = Interpolation\NaturalCubicSpline::interpolate($f⟮x⟯, $start, $end, $n); // input as a callback function
        
        $p(0); // 1
        $p(3); // 16
        
        // Clamped Cubic Spline
        // Returns a piecewise polynomial p(x)
        
        // Input as a set of points
        $points = [[0, 1, 0], [1, 4, -1], [2, 9, 4], [3, 16, 0]];
        
        // Input as a callback function
        $f⟮x⟯ = function ($x) {
            return $x**2 + 2 * $x + 1;
        };
        $f’⟮x⟯ = function ($x) {
            return 2*$x + 2;
        };
        [$start, $end, $n] = [0, 3, 4];
        
        $p = Interpolation\ClampedCubicSpline::interpolate($points);                       // input as a set of points
        $p = Interpolation\ClampedCubicSpline::interpolate($f⟮x⟯, $f’⟮x⟯, $start, $end, $n); // input as a callback function
        
        $p(0); // 1
        $p(3); // 16
        
        // Regular Grid Interpolation
        // Returns a scalar
        
        // Points defining the regular grid
        $xs = [0, 1, 2, 3, 4, 5, 6, 7, 8, 9];
        $ys = [10, 11, 12, 13, 14, 15, 16, 17, 18, 19];
        $zs = [110, 111, 112, 113, 114, 115, 116, 117, 118, 119];
        
        // Data on the regular grid in n dimensions
        $data = [];
        $func = function ($x, $y, $z) {
            return 2 * $x + 3 * $y - $z;
        };
        foreach ($xs as $i => $x) {
            foreach ($ys as $j => $y) {
                foreach ($zs as $k => $z) {
                    $data[$i][$j][$k] = $func($x, $y, $z);
                }
            }
        }
        
        // Constructing a RegularGridInterpolator
        $rgi = new Interpolation\RegularGridInterpolator([$xs, $ys, $zs], $data, 'linear');  // 'nearest' method also available
        
        // Interpolating coordinates on the regular grid
        $coordinates   = [2.21, 12.1, 115.9];
        $interpolation = $rgi($coordinates);  // -75.18
        
        print_r($coordinates);
        echo "<br>";
        print_r($interpolation);
    }
}
