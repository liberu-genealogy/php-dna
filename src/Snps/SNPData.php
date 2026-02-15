<?php

namespace Dna\Snps;

class SNPData
{
    private array $snps = [];
    private array $keys = [];

    public function __construct(array $snps = [])
    {
        $this->setSnps($snps);
    }

    public function getSnps(): array
    {
        return $this->snps;
    }

    public function setSnps(array $snps): void
    {
        $this->snps = $snps;
        $this->keys = array_keys($snps);
    }

    public function count(): int
    {
        return count($this->snps);
    }

    public function filter(callable $callback): array
    {
        return array_filter($this->snps, $callback);
    }

    public function sort(): void
    {
        ksort($this->snps);
        $this->keys = array_keys($this->snps);
    }

    public function merge(SNPData $other): void
    {
        $this->snps = array_merge($this->snps, $other->getSnps());
        $this->keys = array_keys($this->snps);
    }

    public function getChromosomes(): array
    {
        return array_unique(array_column($this->snps, 'chrom'));
    }

    public function getSnpsByChromosome(string $chromosome): array
    {
        return array_filter($this->snps, function($snp) use ($chromosome) {
            return $snp['chrom'] === $chromosome;
        });
    }

    /**
     * Filter SNPs by quality criteria
     *
     * @param array $lowQualitySnps Array of low quality SNP positions
     * @return array Filtered high-quality SNPs
     */
    public function filterLowQuality(array $lowQualitySnps): array
    {
        return array_filter($this->snps, function($snp, $rsid) use ($lowQualitySnps) {
            // Check if this SNP is not in the low quality list
            foreach ($lowQualitySnps as $lowQualSnp) {
                if (isset($lowQualSnp['chrom'], $lowQualSnp['pos']) &&
                    $snp['chrom'] === $lowQualSnp['chrom'] &&
                    $snp['pos'] === $lowQualSnp['pos']) {
                    return false;
                }
            }
            return true;
        }, ARRAY_FILTER_USE_BOTH);
    }

    /**
     * Get SNPs by genotype pattern
     *
     * @param string $pattern Genotype pattern (e.g., 'AA', 'AT', heterozygous, homozygous)
     * @return array Filtered SNPs matching the pattern
     */
    public function getSnpsByGenotype(string $pattern): array
    {
        return array_filter($this->snps, function($snp) use ($pattern) {
            if (!isset($snp['genotype'])) {
                return false;
            }

            $genotype = $snp['genotype'];
            
            // Handle special patterns
            if ($pattern === 'heterozygous') {
                return strlen($genotype) === 2 && $genotype[0] !== $genotype[1];
            } elseif ($pattern === 'homozygous') {
                return strlen($genotype) === 2 && $genotype[0] === $genotype[1];
            }
            
            // Exact match
            return $genotype === $pattern;
        });
    }

    /**
     * Get count of SNPs by chromosome
     *
     * @return array Associative array with chromosome as key and count as value
     */
    public function getChromosomeCounts(): array
    {
        $counts = [];
        foreach ($this->snps as $snp) {
            $chrom = $snp['chrom'] ?? 'unknown';
            $counts[$chrom] = ($counts[$chrom] ?? 0) + 1;
        }
        return $counts;
    }
}