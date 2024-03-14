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
        // Implementation of DataFrame merge operation
    }

    public function select(array $columns) {
        // Implementation of DataFrame select operation
    }

    public function dropDuplicates() {
        // Implementation of DataFrame dropDuplicates operation
    }
}

class SNPAnalysis {
    public function calculateAlleleFrequencies(DataFrame $snps) {
        // Implementation of allele frequency calculation
    }

    public function detectSNPBuild(DataFrame $snps) {
        // Implementation of SNP build detection
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
