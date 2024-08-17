<?php

namespace Dna\Snps;

use Dna\Snps\Analysis\BuildDetector;
use Dna\Snps\Analysis\ClusterOverlapCalculator;

class SNPAnalyzer
{
    private BuildDetector $buildDetector;
    private ClusterOverlapCalculator $clusterOverlapCalculator;

    public function __construct(
        BuildDetector $buildDetector,
        ClusterOverlapCalculator $clusterOverlapCalculator
    ) {
        $this->buildDetector = $buildDetector;
        $this->clusterOverlapCalculator = $clusterOverlapCalculator;
    }

    public function detectBuild(SNPData $snpData): int
    {
        return $this->buildDetector->detectBuild($snpData->getSnps());
    }

    public function computeClusterOverlap(SNPData $snpData, float $threshold = 0.95): array
    {
        return $this->clusterOverlapCalculator->computeClusterOverlap($snpData->getSnps(), $threshold);
    }

    public function determineSex(SNPData $snpData): string
    {
        $xSnps = $snpData->getSnpsByChromosome('X');
        $ySnps = $snpData->getSnpsByChromosome('Y');

        if (empty($xSnps) && empty($ySnps)) {
            return '';
        }

        $xHeterozygous = $this->countHeterozygous($xSnps);
        $yNonNull = $this->countNonNull($ySnps);

        if ($xHeterozygous / count($xSnps) > 0.03) {
            return 'Female';
        } elseif ($yNonNull / count($ySnps) > 0.3) {
            return 'Male';
        }

        return '';
    }

    private function countHeterozygous(array $snps): int
    {
        return count(array_filter($snps, function($snp) {
            return strlen($snp['genotype']) === 2 && $snp['genotype'][0] !== $snp['genotype'][1];
        }));
    }

    private function countNonNull(array $snps): int
    {
        return count(array_filter($snps, function($snp) {
            return $snp['genotype'] !== null;
        }));
    }
}