<?php

require_once 'SNPs.php';
require_once 'Visualization.php';

class MatchKits {
    private $kit1Data;
    private $kit2Data;
    private $matchedData;

    public function loadKitsData($kit1Path, $kit2Path) {
        $this->kit1Data = new Dna\Snps\SNPs($kit1Path);
        $this->kit2Data = new Dna\Snps\SNPs($kit2Path);
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

    public function visualizeMatchedData() {
        $visualization = new Visualization();
        $visualization->plot_chromosomes($this->matchedData, "matched_data.png", "Matched SNP Data", "Build");
    }
}

if (php_sapi_name() == "cli") {
    $matchKits = new MatchKits();
    echo "Enter file path for Kit 1: ";
    $kit1Path = trim(fgets(STDIN));
    echo "Enter file path for Kit 2: ";
    $kit2Path = trim(fgets(STDIN));

    $matchKits->loadKitsData($kit1Path, $kit2Path);
    $matchKits->matchKits();
    $matchKits->visualizeMatchedData();

    echo "Matched data visualization has been generated.\n";
}
?>
