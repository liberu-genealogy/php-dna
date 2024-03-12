<?php

require_once 'SNPs.php';

class Triangulation {

    public static function compareMultipleKits($kitsData) {
        $snpsLists = array_map(function($kit) { return $kit->getSnps(); }, $kitsData);
        $commonSnps = call_user_func_array('array_intersect_key', $snpsLists);
        foreach ($commonSnps as $key => $snp) {
            if (!self::isSnpCommonAcrossAllKits($snp, $kitsData)) {
                unset($commonSnps[$key]);
            }
        }
        return array_values($commonSnps);
    }

    private static function isSnpCommonAcrossAllKits($snp, $kitsData) {
        foreach ($kitsData as $kit) {
            $snps = $kit->getSnps();
            if (!array_key_exists($snp['pos'], $snps) || $snps[$snp['pos']]['genotype'] !== $snp['genotype']) {
                return false;
            }
        }
        return true;
    }
}
?>
