<?php

require_once 'SNPs.php';

class Triangulation {

    public static function compareMultipleKits(array $kitsData): array {
        $snpsLists = self::extractSnpLists($kitsData);
        $commonSnps = self::findCommonSnps($snpsLists);
        return self::filterNonCommonSnps($commonSnps, $kitsData);
    }

    private static function extractSnpLists(array $kitsData): array {
        return array_map(function($kit) { return $kit->getSnps(); }, $kitsData);
    }

    private static function findCommonSnps(array $snpsLists): array {
        return call_user_func_array('array_intersect_key', $snpsLists);
    }

    private static function filterNonCommonSnps(array $commonSnps, array $kitsData): array {
        return array_filter($commonSnps, function($snp) use ($kitsData) {
            return self::isSnpCommonAcrossAllKits($snp, $kitsData);
        });
    }

    private static function isSnpCommonAcrossAllKits(array $snp, array $kitsData): bool {
        return count(array_filter($kitsData, function($kit) use ($snp) {
            $snps = $kit->getSnps();
            return isset($snps[$snp['pos']]) && $snps[$snp['pos']]['genotype'] === $snp['genotype'];
        })) === count($kitsData);
    }
}
?>
