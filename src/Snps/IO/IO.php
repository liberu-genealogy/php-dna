<?php 

namespace Dna\Snps\IO;

class IO
{
    public static function get_empty_snps_dataframe()
    {
        $columns = array("rsid", "chrom", "pos", "genotype");
        $df = array();
        $df[] = $columns;
        return $df;
    }
}
