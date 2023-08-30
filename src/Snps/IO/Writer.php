<?php

namespace Dna\Snps\IO;

use Dna\Snps\SNPs;
use Dna\Snps\SNPsResources;
use League\Csv\Info;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use php_user_filter;
use ZipArchive;

class Writer
{

    /**
     * Writer constructor.
     *
     * @param SNPs|null $snps SNPs to save to file or write to buffer
     * @param string|resource $filename Filename for file to save or buffer to write to
     * @param bool $vcf Flag to save file as VCF
     * @param bool $atomic Atomically write output to a file on the local filesystem
     * @param string $vcfAltUnavailable Representation of VCF ALT allele when ALT is not able to be determined
     * @param string $vcfChromPrefix Prefix for chromosomes in VCF CHROM column
     * @param bool $vcfQcOnly For VCF, output only SNPs that pass quality control
     * @param bool $vcfQcFilter For VCF, populate VCF FILTER column based on quality control results
     * @param array $kwargs Additional parameters to `pandas.DataFrame.to_csv`
     */
    public function __construct(
        protected ?SNPs $snps = null,
        protected $filename = '',
        protected bool $vcf = false,
        protected bool $atomic = true,
        protected string $vcfAltUnavailable = '.',
        protected string $vcfChromPrefix = '',
        protected bool $vcfQcOnly = false,
        protected bool $vcfQcFilter = false,
        protected array $kwargs = []
    ) {
    }

    /**
     * Write SNPs to file or buffer.
     *
     * @return string Path to file in output directory if SNPs were saved, else empty str
     * @return array SNPs with discrepant positions discovered while saving VCF
     */
    public function write()
    {
        if ($this->_vcf) {
            return $this->_writeVcf();
        } else {
            return [$this->_writeCsv()];
        }
    }

    /**
     * Write SNPs to a file or buffer.
     *
     * @param SNPs|null $snps
     * @param string|resource $filename
     * @param bool $vcf
     * @param bool $atomic
     * @param string $vcfAltUnavailable
     * @param bool $vcfQcOnly
     * @param bool $vcfQcFilter
     * @param array $kwargs
     * @return string|array Path to file in output directory if SNPs were saved, else empty str; or SNPs with discrepant positions discovered while saving VCF
     * @throws DeprecationWarning This method will be removed in a future release.
     */
    public static function writeFile(
        ?SNPs $snps = null,
        $filename = '',
        bool $vcf = false,
        bool $atomic = true,
        string $vcfAltUnavailable = '.',
        bool $vcfQcOnly = false,
        bool $vcfQcFilter = false,
        array $kwargs = []
    ) {
        trigger_error(
            "This method will be removed in a future release.",
            E_USER_DEPRECATED
        );
        $w = new self(
            $snps,
            $filename,
            $vcf,
            $atomic,
            $vcfAltUnavailable,
            $vcfQcOnly,
            $vcfQcFilter,
            kwargs: $kwargs
        );
        return $w->write();
    }

    protected function writeCsv()
    {
        /** Write SNPs to a CSV file.
         *
         * @return string Path to file in the output directory if SNPs were saved, else an empty string
         */
        $filename = $this->_filename;
        if (empty($filename)) {
            $ext = ".txt";

            if (array_key_exists("sep", $this->_kwargs) && $this->_kwargs["sep"] == ",") {
                $ext = ".csv";
            }

            $filename = cleanStr($this->_snps->source) . "_" . $this->_snps->assembly . $ext;
        }

        $comment = "# Source(s): " . $this->_snps->source . "\n"
            . "# Build: " . $this->_snps->build . "\n"
            . "# Build Detected: " . ($this->_snps->build_detected ? "true" : "false") . "\n"
            . "# Phased: " . ($this->_snps->phased ? "true" : "false") . "\n"
            . "# SNPs: " . $this->_snps->count . "\n"
            . "# Chromosomes: " . $this->_snps->chromosomesSummary . "\n";

        if (array_key_exists("header", $this->_kwargs)) {
            if (is_bool($this->_kwargs["header"])) {
                if ($this->_kwargs["header"]) {
                    $this->_kwargs["header"] = ["chromosome", "position", "genotype"];
                }
            } else {
                $this->_kwargs["header"] = ["chromosome", "position", "genotype"];
            }
        } else {
            $this->_kwargs["header"] = ["chromosome", "position", "genotype"];
        }

        return saveArrayAsCsv(
            $this->_snps->_snps,
            $this->_snps->_output_dir,
            $filename,
            $comment,
            $this->_atomic,
            $this->_kwargs
        );
    }

    protected function writeVcf()
    {
        /** Write SNPs to a VCF file.
         *
         * @return array Array containing the path to file in output directory if SNPs were saved, else an empty string,
         *               and an array representing discrepant positions discovered while saving VCF
         *
         * @see https://samtools.github.io/hts-specs/VCFv4.2.pdf The Variant Call Format (VCF) Version 4.2 Specification
         */
        $filename = $this->_filename;
        if (empty($filename)) {
            $filename = cleanStr($this->_snps->source) . "_" . $this->_snps->assembly . ".vcf";
        }

        $comment = "##fileformat=VCFv4.2\n"
            . '##fileDate=' . gmdate("Ymd") . "\n"
            . '##source="' . $this->_snps->source . '; snps v' . snpsVersion() . '; https://pypi.org/project/snps/"' . "\n";

        $referenceSequenceChroms = [
            "1", "2", "3", "4", "5", "6", "7", "8", "9", "10",
            "11", "12", "13", "14", "15", "16", "17", "18", "19", "20",
            "21", "22", "X", "Y", "MT"
        ];

        $df = $this->_snps->snps;

        $p = $this->_snps->parallelizer;
        $tasks = [];

        // skip insertions and deletions
        $df = $df->drop(
            $df->filter(function ($row) {
                $genotype = $row['genotype'];
                return !empty($genotype) && (strpos($genotype, 'I') === 0 || strpos($genotype, 'D') === 0);
            })->index()
        );

        $chromsToDrop = [];
        foreach ($df['chrom']->unique() as $chrom) {
            if (!in_array($chrom, $referenceSequenceChroms)) {
                $chromsToDrop[] = $chrom;
                continue;
            }

            $tasks[] = [
                'resources' => $this->_snps->resources,
                'assembly' => $this->_snps->assembly,
                'chrom' => $chrom,
                'snps' => $df->filter(function ($row) use ($chrom) {
                    return $row['chrom'] === $chrom;
                }),
                'cluster' => ($this->_vcfQcOnly || $this->_vcfQcFilter) ? $this->_snps->cluster : '',
                'low_quality_snps' => ($this->_vcfQcOnly || $this->_vcfQcFilter) ? $this->_snps->low_quality : getEmptySnpsArray(),
            ];
        }

        foreach ($chromsToDrop as $chrom) {
            $df = $df->filter(function ($row) use ($chrom) {
                return $row['chrom'] !== $chrom;
            });
        }

        $results = $p([$this, 'createVcfRepresentation'], $tasks);

        $contigs = [];
        $vcf = [];
        $discrepantVcfPosition = [];
        foreach ($results as $result) {
            $contigs[] = $result['contig'];
            $vcf[] = $result['vcf'];
            $discrepantVcfPosition[] = $result['discrepant_vcf_position'];
        }

        $vcf = array_merge([], ...$vcf);
        $discrepantVcfPosition = array_merge([], ...$discrepantVcfPosition);

        $comment .= implode('', $contigs);

        if ($this->_vcfQcFilter && $this->_snps->cluster) {
            $comment .= '##FILTER=<ID=lq,Description="Low quality SNP per Lu et al.: https://doi.org/10.1016/j.csbj.2021.06.040">' . "\n";
        }

        $comment .= '##FORMAT=<ID=GT,Number=1,Type=String,Description="Genotype">' . "\n";
        $comment .= "#CHROM\tPOS\tID\tREF\tALT\tQUAL\tFILTER\tINFO\tFORMAT\tSAMPLE\n";

        return [
            saveArrayAsCsv(
                $vcf,
                $this->_snps->_output_dir,
                $filename,
                $comment,
                false,
                false,
                false,
                ".",
                "\t"
            ),
            $discrepantVcfPosition,
        ];
    }

    protected function computeAlt($ref, $genotype)
    {
        $genotypeAlleles = array_values(array_unique($genotype));

        if (in_array($ref, $genotypeAlleles, true)) {
            if (count($genotypeAlleles) === 1) {
                return $this->_vcfAltUnavailable;
            } else {
                $refIndex = array_search($ref, $genotypeAlleles, true);
                unset($genotypeAlleles[$refIndex]);
                return array_shift($genotypeAlleles);
            }
        } else {
            sort($genotypeAlleles);
            return implode(",", $genotypeAlleles);
        }
    }

    protected function computeGenotype($ref, $alt, $genotype)
    {
        $alleles = [$ref];

        if ($this->_snps->phased) {
            $separator = "|";
        } else {
            $separator = "/";
        }

        if (!is_null($alt)) {
            $alleles = array_merge($alleles, explode(",", $alt));
        }

        if (count($genotype) === 2) {
            return array_search($genotype[0], $alleles, true) . $separator . array_search($genotype[1], $alleles, true);
        } else {
            return array_search($genotype[0], $alleles, true);
        }
    }


    protected function createVcfRepresentation($task)
    {
        $resources = $task["resources"];
        $assembly = $task["assembly"];
        $chrom = $task["chrom"];
        $snps = $task["snps"];
        $cluster = $task["cluster"];
        $lowQualitySnps = $task["low_quality_snps"];

        if (count(array_filter($snps["genotype"]->notnull())) === 0) {
            return [
                "contig" => "",
                "vcf" => [],
                "discrepant_vcf_position" => [],
            ];
        }

        $seqs = $resources->getReferenceSequences($assembly, [$chrom]);
        $seq = $seqs[$chrom];

        $contig = sprintf(
            '##contig=<ID=%s,URL=%s,length=%s,assembly=%s,md5=%s,species="%s">' . PHP_EOL,
            $seq->ID,
            $seq->url,
            $seq->length,
            $seq->build,
            $seq->md5,
            $seq->species
        );

        if ($this->_vcfQcOnly && $cluster) {
            // Drop low quality SNPs if SNPs object maps to a cluster
            $snps = array_filter($snps->drop($this->arrayIntersectAssoc($snps->index, $lowQualitySnps->index)));
        }

        if ($this->_vcfQcFilter && $cluster) {
            // Initialize filter for all SNPs if SNPs object maps to a cluster
            $snps["filter"] = "PASS";
            // Then indicate SNPs that were identified as low quality
            foreach ($lowQualitySnps->index as $index) {
                if (isset($snps[$index])) {
                    $snps[$index]["filter"] = "lq";
                }
            }
        }

        $snps = array_values($snps);

        $df = [
            "CHROM" => [],
            "POS" => [],
            "ID" => [],
            "REF" => [],
            "ALT" => [],
            "QUAL" => [],
            "FILTER" => [],
            "INFO" => [],
            "FORMAT" => [],
            "SAMPLE" => [],
        ];

        // Set data types for the arrays
        $dataTypes = [
            "CHROM" => "object",
            "POS" => "uint32",
            "ID" => "object",
            "REF" => "object",
            "ALT" => "object",
            "QUAL" => "float32",
            "FILTER" => "object",
            "INFO" => "object",
            "FORMAT" => "object",
            "SAMPLE" => "object",
        ];

        foreach ($df as $col => $values) {
            $df[$col] = array_fill(0, count($snps), $values);
        }

        foreach ($snps as $index => $row) {
            $df["CHROM"][$index] = $this->_vcfChromPrefix . $row["chrom"];
            $df["POS"][$index] = $row["pos"];
            $df["ID"][$index] = $row["rsid"];
        }

        if ($this->_vcfQcFilter && $cluster) {
            foreach ($snps as $index => $row) {
                $df["FILTER"][$index] = $row["filter"];
            }
        }

        // Drop SNPs with discrepant positions (outside reference sequence)
        $discrepantVcfPosition = [];
        foreach ($snps as $index => $row) {
            if ($row["pos"] - $seq->start < 0 || $row["pos"] - $seq->start > $seq->length - 1) {
                $discrepantVcfPosition[] = $row;
                unset($snps[$index]);
            }
        }

        // Fill REF column based on the sequence data
        foreach ($snps as $index => $row) {
            $df["REF"][$index] = chr($seq->sequence[$row["pos"] - $seq->start]);
        }

        $df["FORMAT"] = "GT";

        $seq->clear();

        foreach ($snps as $index => $row) {
            $df["genotype"][$index] = $row["genotype"];
        }

        $temp = array_filter($df["genotype"], function ($value) {
            return !is_null($value);
        });

        foreach ($temp as $index => $value) {
            $df["ALT"][$index] = $this->_computeAlt($df["REF"][$index], $value);
        }

        $temp = array_filter($df["genotype"], function ($value) {
            return !is_null($value);
        });

        foreach ($temp as $index => $value) {
            $df["SAMPLE"][$index] = $this->_computeGenotype($df["REF"][$index], $df["ALT"][$index], $value);
        }

        foreach ($df["SAMPLE"] as $index => $value) {
            if (is_null($value)) {
                $df["SAMPLE"][$index] = "./.";
            }
        }

        unset($df["genotype"]);

        return [
            "contig" => $contig,
            "vcf" => $df,
            "discrepant_vcf_position" => $discrepantVcfPosition,
        ];
    }
}
