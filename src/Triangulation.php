<?php

namespace Dna;

use Dna\Snps\SNPs;
use Exception;

class Triangulation {

    /**
     * Compare multiple DNA kits and find common SNPs
     *
     * @param SNPs[] $kitsData Array of SNPs objects
     * @return array Common SNPs across all kits
     * @throws Exception If there's an error during comparison
     */
    public static function compareMultipleKits(array $kitsData): array {
        try {
            self::validateInput($kitsData);
            $snpsLists = self::extractSnpLists($kitsData);
            $commonSnps = self::findCommonSnps($snpsLists);
            return self::filterNonCommonSnps($commonSnps, $kitsData);
        } catch (Exception $e) {
            throw new Exception("Error comparing multiple kits: " . $e->getMessage());
        }
    }

    /**
     * Validate input kits data
     *
     * @param array $kitsData Array of SNPs objects
     * @throws Exception If input is invalid
     */
    private static function validateInput(array $kitsData): void {
        if (count($kitsData) < 3) {
            throw new Exception("At least three DNA kits are required for triangulation.");
        }
        foreach ($kitsData as $kit) {
            if (!$kit instanceof SNPs) {
                throw new Exception("Invalid input: All elements must be instances of SNPs class.");
            }
        }
    }

    /**
     * Extract SNP lists from kits data
     *
     * @param SNPs[] $kitsData Array of SNPs objects
     * @return array Array of SNP lists
     */
    private static function extractSnpLists(array $kitsData): array {
        return array_map(function(SNPs $kit) { return $kit->getSnps(); }, $kitsData);
    }

    /**
     * Find common SNPs across all kits
     *
     * @param array $snpsLists Array of SNP lists
     * @return array Common SNPs
     */
    private static function findCommonSnps(array $snpsLists): array {
        return call_user_func_array('array_intersect_key', $snpsLists);
    }

    /**
     * Filter non-common SNPs
     *
     * @param array $commonSnps Array of common SNPs
     * @param SNPs[] $kitsData Array of SNPs objects
     * @return array Filtered common SNPs
     */
    private static function filterNonCommonSnps(array $commonSnps, array $kitsData): array {
        return array_filter($commonSnps, function($snp) use ($kitsData) {
            return self::isSnpCommonAcrossAllKits($snp, $kitsData);
        });
    }

    /**
     * Check if SNP is common across all kits
     *
     * @param array $snp SNP to check
     * @param SNPs[] $kitsData Array of SNPs objects
     * @return bool True if SNP is common across all kits, false otherwise
     */
    private static function isSnpCommonAcrossAllKits(array $snp, array $kitsData): bool {
        return count(array_filter($kitsData, function(SNPs $kit) use ($snp) {
            $snps = $kit->getSnps();
            return isset($snps[$snp['pos']]) && $snps[$snp['pos']]['genotype'] === $snp['genotype'];
        })) === count($kitsData);
    }
}
