<?php

require_once 'SNPs.php';
require_once 'Visualization.php';

class MatchKits {
    private $kitsData = [];
    private $matchedData;

    public function loadKitsData($kitPaths) {
        foreach ($kitPaths as $path) {
            $this->kitsData[] = new Dna\Snps\SNPs($path);
        }
    }

    public function matchKits() {
        $this->matchedData = []; // Initialize matched data array
        foreach ($this->kit1Data->getSnps() as $snp1) {
            foreach ($this->kit2Data->getSnps() as $snp2) {
                if ($snp1['pos'] == $snp2['pos'] && $snp1['genotype'] == $snp2['genotype']) {
                    $this->matchedData[] = $snp1; // Add matching SNP to matched data
                }
            }
        }
    }

    public function visualizeMatchedData($format) {
        $visualization = new Visualization();
        $visualization->plot_chromosomes($this->matchedData, "matched_data." . $format, "Matched SNP Data", "Build", $format);
    }
}

if (php_sapi_name() == "cli") {
    $matchKits = new MatchKits();
    echo "Enter the number of kits to compare: ";
    $numKits = trim(fgets(STDIN));
    $kitPaths = [];
    for ($i = 0; $i < $numKits; $i++) {
        echo "Enter file path for Kit " . ($i + 1) . ": ";
        $kitPaths[] = trim(fgets(STDIN));
    }

    $matchKits->loadKitsData($kitPaths);
    $matchKits->matchKits();
    $matchKits->visualizeMatchedData();

    echo "Matched data visualization has been generated.\n";
}
?>
    public function triangulateKits() {
        $this->matchedData = []; // Initialize matched data array
        $snpsLists = array_map(function($kit) { return $kit->getSnps(); }, $this->kitsData);
        $commonSnps = call_user_func_array('array_intersect_key', $snpsLists);
        foreach ($commonSnps as $snp) {
            $this->matchedData[] = $snp; // Add common SNP to matched data
        }
    }
