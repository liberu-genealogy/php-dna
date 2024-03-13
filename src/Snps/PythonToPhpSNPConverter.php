<?php

namespace Dna\Snps;

use SplFileObject;
use InvalidArgumentException;

class PythonToPhpSNPConverter
{
    public function __construct(private string $filePath)
    {
        if (!file_exists($this->filePath)) {
            throw new InvalidArgumentException("File does not exist: {$this->filePath}");
        }
    }

    public function readSNPData(): array
    {
        $file = new SplFileObject($this->filePath);
        $snps = [];
        while (!$file->eof()) {
            $line = $file->fgets();
            $snps[] = $this->parseSNPLine($line);
        }
        return $snps;
    }

    private function parseSNPLine(string $line): array
    {
        // Assuming the SNP data is comma-separated
        return str_getcsv($line);
    }

    public function filterSNPs(array $snps, array $criteria): array
    {
        return array_filter($snps, function ($snp) use ($criteria) {
            foreach ($criteria as $key => $value) {
                if ($snp[$key] !== $value) {
                    return false;
                }
            }
            return true;
        });
    }

    public function detectSNPBuild(array $snps): string
    {
        // Example implementation, actual logic will depend on SNP data structure
        $build = match (true) {
            array_key_exists('build37', $snps[0]) => '37',
            array_key_exists('build38', $snps[0]) => '38',
            default => 'unknown',
        };
        return $build;
    }

    public function handleSNPDataFrame(array &$snps): void
    {
        usort($snps, function ($a, $b) {
            return $a['position'] <=> $b['position'] ?? 0;
        });
    }
}
