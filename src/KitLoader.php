<?php
namespace Dna\Snps;

/**
 * Loads DNA kit data from files
 */
class KitLoader
{
    /**
     * Load DNA kit data from the provided file paths
     *
     * @param string[] $kitPaths The file paths to load
     * @return SNPs[] The loaded DNA kit data  
     */
    public function loadKitsData(array $kitPaths): array
    {
        $kitsData = [];
        
        foreach ($kitPaths as $path) {
            $kitsData[] = new SNPs($path);
        }
        
        return $kitsData;
    }
}