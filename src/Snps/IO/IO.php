<?php 

namespace Dna\Snps\IO;

class IO
{
    public static function get_empty_snps_dataframe()
    {
        $columns = array("rsid" => null, "chrom" => null, "pos" => null, "genotype" => null);
        $df = array();
        $df[] = $columns;
        return $df;
    }
}
