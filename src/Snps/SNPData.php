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
}