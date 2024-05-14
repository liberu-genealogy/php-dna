<?php

require_once 'SNPs.php';
require_once 'Visualization.php';

/**
 * Matches SNP data between DNA kits
 */
class MatchKits
{
    /**
     * @var SNPs[] The DNA kit data to match
     */
    private array $kitsData = [];
    
    /**
     * @var array The matched SNP data
     */
    private array $matchedData = [];

    /**
     * Match the loaded DNA kits
     */
    public function matchKits(): void
    {
        $this->matchedData = []; // Reset matched data
        
        foreach ($this->kitsData[0]->getSnps() as $snp1) {
            foreach ($this->kitsData[1]->getSnps() as $snp2) {
                if ($snp1['pos'] === $snp2['pos'] && $snp1['genotype'] === $snp2['genotype']) {
                    $this->matchedData[] = $snp1;
                }
            }
        }
    }
    
    /**
     * @return array The matched SNP data
     */
    public function getMatchedData(): array 
    {
        return $this->matchedData;
    }
    
    /**
     * Load DNA kit data
     *
     * @param SNPs[] $kitsData The kit data to load
     */
    public function setKitsData(array $kitsData): void
    {
        $this->kitsData = $kitsData;
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
