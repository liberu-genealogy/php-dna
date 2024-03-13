<?php

namespace Dna\Snps\IO;

use Dna\Snps\EnsemblRestClient;
use Dna\Snps\IO\FileHandler;
use Dna\Snps\IO\DataManipulator;
use Symfony\Component\HttpClient\HttpClient;
use League\Csv\Writer as CsvWriter;
use League\Csv\CannotInsertRecord;
use ZipArchive;
use Exception;

class Writer
{

    /**
     * Writer constructor.
     *
     * @param SNPs|null $snps Updated SNPs object to save to file or write to buffer
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
        protected readonly ?\Dna\Snps\SNPs $snps = null,
        protected readonly string|resource $filename = '',
        protected readonly bool $vcf = false,
        protected readonly bool $atomic = true,
        protected readonly string $vcfAltUnavailable = '.',
        protected readonly string $vcfChromPrefix = '',
        protected readonly bool $vcfQcOnly = false,
        protected readonly bool $vcfQcFilter = false,
        protected readonly array $kwargs = []
    ) {}

    /**
     * Write SNPs to file or buffer.
     *
     * @return string Path to file in output directory if SNPs were saved, else empty str
     * @return array SNPs with discrepant positions discovered while saving VCF
     */
    public function write()
    {
        // Determine the file format based on the extension or the $vcf flag, updated to handle new data formats
        $fileExtension = strtolower(pathinfo($this->filename, PATHINFO_EXTENSION));
        if ($this->vcf || $fileExtension === 'vcf') {
            return $this->_writeVcf();
        } elseif ($fileExtension === 'csv' || $fileExtension === 'txt') {
            return $this->_writeCsv();
        } else {
            throw new Exception("Unsupported file format: " . $fileExtension);
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
        // Prepare CSV writer, updated to handle new data formats
        $csvWriter = CsvWriter::createFromPath($this->filename, 'w+');
        $csvWriter->setOutputBOM(CsvWriter::BOM_UTF8);

        // Set headers if specified
        if (!empty($this->kwargs['header'])) {
            try {
                $csvWriter->insertOne($this->kwargs['header']);
            } catch (CannotInsertRecord $e) {
                throw new Exception("Failed to insert header: " . $e->getMessage());
            }
        }

        // Insert data
        try {
            $csvWriter->insertAll($this->snps->toArray());
        } catch (CannotInsertRecord $e) {
            throw new Exception("Failed to write CSV data: " . $e->getMessage());
        }

        return $this->filename;
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
        // Updated to handle new data structures introduced in the SNPs class update
        $resources = $task["resources"];
        // Ensure compatibility with PHP 8.3 features and type declarations
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
