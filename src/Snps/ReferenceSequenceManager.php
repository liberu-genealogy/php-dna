<?php

namespace Dna\Snps;

use Dna\Snps\ReferenceSequence;

class ReferenceSequenceManager
{
    private array $_reference_sequences = [];
    private array $validAssemblies = ["NCBI36", "GRCh37", "GRCh38"];

    public function __construct()
    {
        $this->init_resource_attributes();
    }

    private function init_resource_attributes(): void
    {
        $this->_reference_sequences = [];
    }

    public function getReferenceSequences(string $assembly = "GRCh37", array $chroms = ["1", "2", "3", "4", "5", "6", "7", "8", "9", "10", "11", "12", "13", "14", "15", "16", "17", "18", "19", "20", "21", "22", "X", "Y", "MT"]): array
    {
        if (!in_array($assembly, $this->validAssemblies)) {
            error_log("Invalid assembly");
            return [];
        }

        if (!$this->referenceChromsAvailable($assembly, $chroms)) {
            // Placeholder for logic to fetch paths and URLs for reference sequences
            $urls = [];
            $paths = [];
            $this->_reference_sequences[$assembly] = $this->createReferenceSequences($assembly, $chroms, $urls, $paths);
        }

        return $this->_reference_sequences[$assembly];
    }

    private function referenceChromsAvailable(string $assembly, array $chroms): bool
    {
        // Placeholder for actual availability check logic
        return false;
    }

    protected function createReferenceSequences(string $assembly, array $chroms, array $urls, array $paths): array
    {
        $seqs = [];

        foreach ($paths as $i => $path) {
            if (!$path) {
                continue;
            }

            $seqs[$chroms[$i]] = new ReferenceSequence(
                $chroms[$i],
                $urls[$i],
                realpath($path),
                $assembly,
                "Homo sapiens",
                "x"
            );
        }

        return $seqs;
    }
}
