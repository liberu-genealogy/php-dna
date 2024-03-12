<?php

namespace Dna\Snps\Analysis;

class ClusterOverlapCalculator
{
    public function computeClusterOverlap(array $snps, array $chipClusters, float $clusterOverlapThreshold = 0.95): array
    {
        $overlapResults = [];
        foreach ($chipClusters as $clusterId => $clusterData) {
            $snpsInCluster = array_filter($snps, function ($snp) use ($clusterData) {
                return in_array($snp['chrom'], $clusterData['chromosomes']) && $snp['pos'] >= $clusterData['start'] && $snp['pos'] <= $clusterData['end'];
            });

            $snpsInCommon = count($snpsInCluster);
            $totalSnpsInCluster = count($clusterData['snps']);
            $overlapWithCluster = $snpsInCommon / $totalSnpsInCluster;
            $overlapWithSelf = $snpsInCommon / count($snps);

            if ($overlapWithCluster > $clusterOverlapThreshold && $overlapWithSelf > $clusterOverlapThreshold) {
                $overlapResults[$clusterId] = [
                    'overlapWithCluster' => $overlapWithCluster,
                    'overlapWithSelf' => $overlapWithSelf,
                    'snpsInCommon' => $snpsInCommon,
                    'totalSnpsInCluster' => $totalSnpsInCluster,
                ];
            }
        }

        return $overlapResults;
    }
}
