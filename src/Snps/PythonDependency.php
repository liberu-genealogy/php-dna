<?php

namespace Dna\Snps;

use MathPHP\Statistics\Average;
use MathPHP\LinearAlgebra\MatrixFactory;

class DataFrame {
    private $data;
    private $columns;

    public function __construct(array $data = [], array $columns = []) {
        $this->data = $data;
        $this->columns = $columns;
    }

    public function filter(callable $callback) {
        $filteredData = array_filter($this->data, $callback);
        return new self($filteredData, $this->columns);
    }

    public function merge(DataFrame $other, string $joinType = 'inner', ?string $on = null) {
        // Implement the logic to merge two DataFrames based on the join type and column(s)
        // Example implementation:
        $mergedData = [];
        
        foreach ($this->data as $row1) {
            foreach ($other->data as $row2) {
                if ($on !== null && $row1[$on] === $row2[$on]) {
                    $mergedRow = array_merge($row1, $row2);
                    $mergedData[] = $mergedRow;
                } elseif ($on === null) {
                    $mergedRow = array_merge($row1, $row2);
                    $mergedData[] = $mergedRow;
                }
            }
        }
        
        return new self($mergedData, array_merge($this->columns, $other->columns));
    }

    public function select(array $columns) {
        // Implement the logic to select a subset of columns from the DataFrame
        // Example implementation:
        $selectedData = [];
        
        foreach ($this->data as $row) {
            $selectedRow = [];
            foreach ($columns as $column) {
                $selectedRow[$column] = $row[$column];
            }
            $selectedData[] = $selectedRow;
        }
        
        return new self($selectedData, $columns);
    }

    public function dropDuplicates() {
        // Implement the logic to remove duplicate rows from the DataFrame
        // Example implementation:
        $uniqueData = [];
        
        foreach ($this->data as $row) {
            if (!in_array($row, $uniqueData)) {
                $uniqueData[] = $row;
            }
        }
        
        return new self($uniqueData, $this->columns);
    }
}

class SNPAnalysis {
    public function calculateAlleleFrequencies(DataFrame $snps) {
        // Implement the logic to calculate allele frequencies for the given SNPs data
        // Example implementation:
        $alleleFrequencies = [];
        
        foreach ($snps->data as $snp) {
            $alleles = str_split($snp['genotype']);
            foreach ($alleles as $allele) {
                if (!isset($alleleFrequencies[$allele])) {
                    $alleleFrequencies[$allele] = 0;
                }
                $alleleFrequencies[$allele]++;
            }
        }
        
        $totalAlleles = array_sum($alleleFrequencies);
        foreach ($alleleFrequencies as &$frequency) {
            $frequency /= $totalAlleles;
        }
        
        return $alleleFrequencies;
    }

    public function detectSNPBuild(DataFrame $snps) {
        // Implement the logic to detect the SNP build based on the given SNPs data
        // Example implementation:
        $buildCounts = [];
        
        foreach ($snps->data as $snp) {
            $build = $snp['build'];
            if (!isset($buildCounts[$build])) {
                $buildCounts[$build] = 0;
            }
            $buildCounts[$build]++;
        }
        
        $maxCount = 0;
        $detectedBuild = null;
        foreach ($buildCounts as $build => $count) {
            if ($count > $maxCount) {
                $maxCount = $count;
                $detectedBuild = $build;
            }
        }
        
        return $detectedBuild;
    }
}

class MathOperations {
    public function calculateStandardDeviation(array $data) {
        return Average::standardDeviation($data);
    }

    public function createMatrix(array $data) {
        return MatrixFactory::create($data);
    }
}
