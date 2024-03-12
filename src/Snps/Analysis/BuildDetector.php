<?php

namespace Dna\Snps\Analysis;

class BuildDetector
{
    public function detectBuild(array $snps): int
    {
        $buildPositions = [
            "rs3094315" => [36 => 742429, 37 => 752566, 38 => 817186],
            "rs11928389" => [36 => 50908372, 37 => 50927009, 38 => 50889578],
            "rs2500347" => [36 => 143649677, 37 => 144938320, 38 => 148946169],
            "rs964481" => [36 => 27566744, 37 => 27656823, 38 => 27638706],
            "rs2341354" => [36 => 908436, 37 => 918573, 38 => 983193],
            "rs3850290" => [36 => 22315141, 37 => 23245301, 38 => 22776092],
            "rs1329546" => [36 => 135302086, 37 => 135474420, 38 => 136392261],
        ];

        foreach ($snps as $snp) {
            foreach ($buildPositions as $rsid => $positions) {
                if ($snp['rsid'] === $rsid) {
                    foreach ($positions as $build => $position) {
                        if ($snp['pos'] === $position) {
                            return $build;
                        }
                    }
                }
            }
        }

        return 0; // Default or unable to detect
    }
}
