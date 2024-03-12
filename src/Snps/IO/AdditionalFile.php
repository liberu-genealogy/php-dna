<?php

namespace Dna\Snps\IO;

use Dna\Snps\SNPs;
use RuntimeException;

class SNPDataParser
{
    public function parseFromFile(string $filePath): SNPs
    {
        $fileContent = file_get_contents($filePath);
        if ($fileContent === false) {
            throw new RuntimeException("Failed to read file: $filePath");
        }
        return $this->parseFromText($fileContent);
    }

    public function parseFromText(string $rawData): SNPs
    {
        $snps = new SNPs();
        $lines = explode("\n", $rawData);
        foreach ($lines as $line) {
            if (empty($line) || $line[0] === '#') {
                continue;
            }
            $parts = explode("\t", $line);
            if (count($parts) < 3) {
                continue;
            }
            [$chromosome, $position, $genotype] = $parts;
            $snps->addSNP($chromosome, $position, $genotype);
        }
        return $snps;
    }
}
