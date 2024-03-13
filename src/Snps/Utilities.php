<?php

namespace Dna\Snps;

class Utilities
{
    public static function convertToCamelCase(string $input): string
    {
        $words = explode('_', strtolower($input));
        $camelCase = $words[0];
        array_shift($words);
        foreach ($words as $word) {
            $camelCase .= ucfirst($word);
        }
        return $camelCase;
    }

    public static function parseCsvLine(string $line): array
    {
        if (empty($line)) {
            return [];
        }
        return str_getcsv($line);
    }

    public static function detectBuildFromSNPs(array $snps): string
    {
        foreach ($snps as $snp) {
            if (array_key_exists('build37', $snp)) {
                return '37';
            } elseif (array_key_exists('build38', $snp)) {
                return '38';
            }
        }
        return 'unknown';
    }

    public static function sortSNPsByPosition(array &$snps): void
    {
        usort($snps, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });
    }
}
