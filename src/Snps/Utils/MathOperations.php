<?php

namespace Dna\Snps\Utils;

use MathPHP\Statistics\Average;
use MathPHP\Statistics\Descriptive;

class MathOperations
{
    public static function mean(array $numbers): float
    {
        return Average::mean($numbers);
    }

    public static function median(array $numbers): float
    {
        return Average::median($numbers);
    }

    public static function standardDeviation(array $numbers): float
    {
        return Descriptive::standardDeviation($numbers, true);
    }

    public static function variance(array $numbers): float
    {
        return Descriptive::variance($numbers, true);
    }

    public static function sum(array $numbers): float
    {
        return array_sum($numbers);
    }

    public static function min(array $numbers): float
    {
        return min($numbers);
    }

    public static function max(array $numbers): float
    {
        return max($numbers);
    }
}
