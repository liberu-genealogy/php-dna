<?php

    // You may need to find alternative libraries for numpy, pandas, and snps in PHP, as these libraries are specific to Python
    // For numpy, consider using a library such as MathPHP: https://github.com/markrogoyski/math-php
    // For pandas, you can use DataFrame from https://github.com/aberenyi/php-dataframe, though it is not as feature-rich as pandas
    // For snps, you'll need to find a suitable PHP alternative or adapt the Python code to PHP

    // import copy // In PHP, you don't need to import the 'copy' module, as objects are automatically copied when assigned to variables

    // from itertools import groupby, count // PHP has built-in support for array functions that can handle these operations natively

    // import logging // For logging in PHP, you can use Monolog: https://github.com/Seldaek/monolog
    use Monolog\Logger;
    use Monolog\Handler\StreamHandler;

    // import os, re, warnings
    // PHP has built-in support for file operations, regex, and error handling, so no need to import these modules

    // import numpy as np // See the note above about using MathPHP or another PHP library for numerical operations
    // import pandas as pd // See the note above about using php-dataframe or another PHP library for data manipulation

    // from pandas.api.types import CategoricalDtype // If using php-dataframe, check documentation for similar functionality

    // For snps.ensembl, snps.resources, snps.io, and snps.utils, you'll need to find suitable PHP alternatives or adapt the Python code
    // from snps.ensembl import EnsemblRestClient
    // from snps.resources import Resources
    // from snps.io import Reader, Writer, get_empty_snps_dataframe
    // from snps.utils import Parallelizer

    // Set up logging
    $logger = new Logger('my_logger');
    $logger->pushHandler(new StreamHandler('php://stderr', Logger::DEBUG));

    class SNPs
    {
        /**
         * SNPs constructor.
         *
         * @param string $file                Input file path
         * @param bool   $only_detect_source  Flag to indicate whether to only detect the source
         * @param bool   $assign_par_snps     Flag to indicate whether to assign par_snps
         * @param string $output_dir          Output directory path
         * @param string $resources_dir       Resources directory path
         * @param bool   $deduplicate         Flag to indicate whether to deduplicate
         * @param bool   $deduplicate_XY_chrom Flag to indicate whether to deduplicate XY chromosome
         * @param bool   $deduplicate_MT_chrom Flag to indicate whether to deduplicate MT chromosome
         * @param bool   $parallelize         Flag to indicate whether to parallelize
         * @param int    $processes           Number of processes to use for parallelization
         * @param array  $rsids               Array of rsids
         */
        private $_file;
        private $_only_detect_source;
        private $_snps;
        private $_duplicate;
        private $_discrepant_XY;
        private $_heterozygous_MT;
        private $_discrepant_vcf_position;
        private $_low_quality;
        private $_discrepant_merge_positions;
        private $_discrepant_merge_genotypes;
        private $_source;
        private $_phased;
        private $_build;
        private $_build_detected;
        private $_output_dir;
        private $_resources;
        private $_parallelizer;
        private $_cluster;
        private $_chip;
        private $_chip_version;

        private $_snps;

        private $_duplicate;

        private $_discrepant_XY;

        private $_heterozygous_MT;

        private $_discrepant_vcf_position;

        private $_discrepant_merge_positions;

        private $_discrepant_merge_genotypes;

        private $_source;

        private $_phased;

        private $_build;

        private $_build_detected;
         
        /**
         * SNPs constructor.
         *
         * @param string $file           Input file path
         * @param bool   $only_detect_source  Flag to indicate whether to only detect the source
         * @param string $output_dir     Output directory path
         * @param string $resources_dir  Resources directory path
         * @param bool   $parallelize    Flag to indicate whether to parallelize
         * @param int    $processes      Number of processes to use for parallelization
         */
        public function __construct($file, $only_detect_source, $output_dir, $resources_dir, $parallelize, $processes)
        {
            $this->_file = $file;
            $this->_only_detect_source = $only_detect_source;
            $this->_snps = $this->get_empty_snps_dataframe();
            $this->_duplicate = $this->get_empty_snps_dataframe();
            $this->_discrepant_XY = $this->get_empty_snps_dataframe();
            $this->_heterozygous_MT = $this->get_empty_snps_dataframe();
            $this->_discrepant_vcf_position = $this->get_empty_snps_dataframe();
            $this->_low_quality = $this->_snps->index;
            $this->_discrepant_merge_positions = new DataFrame();
            $this->_discrepant_merge_genotypes = new DataFrame();
            $this->_source = [];
            $this->_phased = false;
            $this->_build = 0;
            $this->_build_detected = false;
            $this->_output_dir = $output_dir;
            $this->_resources = new Resources($resources_dir);
            $this->_parallelizer = new Parallelizer($parallelize, $processes);
            $this->_cluster = "";
            $this->_chip = "";
            $this->_chip_version = "";
        }
        if ($file) {
            $d = $this->_read_raw_data($file, $only_detect_source, $rsids);
        
            // Replace multiple rsids separated by commas in index with the first rsid. E.g. rs1,rs2 -> rs1
            $multi_rsids = [];
            foreach ($d["snps"] as $multi_rsid) {
                if (count(explode(",", $multi_rsid)) > 1) {
                    $multi_rsids[$multi_rsid] = explode(",", $multi_rsid)[0];
                }
            }
            $d["snps"] = array_replace_key($d["snps"], $_rsids);
        
            $this->_snps = $d["snps"];
            $this->_source = (strpos($d["source"], ", ") !== false) ? explode(", ", $d["source"]) : [$d["source"]];
            $this->_phased = $d["phased"];
            $this->_build = $d["build"];
            $this->_build_detected = $d["build"] ? true : false;
        
            if (!empty($this->_snps)) {
                $this->sort();
        
                if ($deduplicate) {
                    $this->_deduplicate_rsids();
                }
        
                // use build detected from `read` method or comments, if any
                // otherwise use SNP positions to detect build
                if (!$this->_build_detected) {
                    $this->_build = $this->detect_build();
                    $this->_build_detected = $this->_build ? true : false;
        
                    if (!$this->_build) {
                        $this->_build = 37; // assume Build 37 / GRCh37 if not detected
                    } else {
                        $this->_build_detected = true;
                    }
                }
        
                if ($assign_par_snps) {
                    $this->_assign_par_snps();
                    $this->sort();
                }
        
                if ($deduplicate_XY_chrom) {
                    if ($deduplicate_XY_chrom === true && $this->determine_sex() === "Male" || $this->determine_sex(chrom: $deduplicate_XY_chrom) === "Male") {
                        $this->_deduplicate_XY_chrom();
                    }
                }
        
                if ($deduplicate_MT_chrom) {
                    $this->_deduplicate_MT_chrom();
                }
        
            } else {
                // Use PHP's error_log function or other logging library to display a warning
                error_log("no SNPs loaded...");
            }
        }
        
        /**
         * Get the default CPU count.
         *
         * @return int Default CPU count
         */
        private static function default_cpu_count(): int
        {
            return sys_get_temp_dir();
        }

        /**
         * Get the length of the SNPs.
         *
         * @return int The count of SNPs
         */
        public function length()
        {
            return $this->count;
        }

        /**
         * Convert the SNPs object to a string representation.
         *
         * @return string The string representation of the SNPs object
         */
        public function __toString()
        {
            if (is_string($this->file)) {
                // If the file path is a string, return SNPs with the basename of the file
                return "SNPs('" . basename($this->file) . "')";
            } else {
                // If the file path is not a string, return SNPs with <bytes>
                return "SNPs(<bytes>)";
            }
        }
        
        /**
         * Get the source as a string.
         *
         * @return string The source string
         */
        public function getSource(): string
        {
            return implode(", ", $this->_source);
        }

        /**
         * Get the SNPs as an array.
         *
         * @return array The SNPs array
         */
        public function getSnps(): array
        {
            return $this->_snps;
        }

        /**
         * Identify low quality SNPs.
         *
         * @return void
         */
        public function identify_low_quality_snps(): void
        {
            // Implement the method in PHP.
        }
        
        /**
         * Get the SNPs after quality control filtering.
         *
         * @return array The SNPs array after quality control filtering
         */
        public function getSnpsQc(): array
        {
            if (count($this->_low_quality) == 0) {
                // Ensure low quality SNPs, if any, are identified
                $this->identify_low_quality_snps();
            }

            if (count($this->_low_quality) > 0) {
                // Filter out low quality SNPs
                return array_diff_key($this->_snps, array_flip($this->_low_quality));
            } else {
                // No low quality SNPs to filter
                return $this->_snps;
            }
        }

        /**
         * Get the duplicate SNPs.
         *
         * @return array The duplicate SNPs array
         */
        public function getDuplicate(): array
        {
            return $this->_duplicate;
        }
        
        public function getDiscrepantXY(): array
        {
            // Discrepant XY SNPs.
            //
            // A discrepant XY SNP is a heterozygous SNP in the non-PAR region of the X
            // or Y chromosome found during deduplication for a detected male genotype.
            //
            // Returns
            // -------
            // array
            //     normalized "snps" array
            return $this->_discrepantXY;
        }
    
        public function getHeterozygousMT(): array
        {
            // Heterozygous SNPs on the MT chromosome found during deduplication.
            //
            // Returns
            // -------
            // array
            //     normalized "snps" array
            return $this->_heterozygousMT;
        }
        
        public function getDiscrepantVcfPosition(): array
        {
            // SNPs with discrepant positions discovered while saving VCF.
            //
            // Returns
            // -------
            // array
            //     normalized "snps" array
            return $this->_discrepantVcfPosition;
        }
    
        private array $lowQuality = [];
        private DataFrame $_snps;
        private DataFrame $_discrepant_merge_positions;
    
        public function getLowQuality(): DataFrame
        {
            if (count($this->lowQuality) === 0) {
                // Ensure low quality SNPs, if any, are identified
                $this->identify_low_quality_snps();
            }
    
            return $this->_snps->loc($this->lowQuality);
        }
    
        public function getDiscrepantMergePositions(): DataFrame
        {
            // Get the DataFrame of SNPs with discrepant merge positions.
            //
            // Returns
            // -------
            // DataFrame
            //     DataFrame containing SNPs with discrepant merge positions
            return $this->_discrepant_merge_positions;
        }
        
        public function getDiscrepantMergeGenotypes(): DataFrame {
            // Get the DataFrame of SNPs with discrepant merge genotypes.
            //
            // Returns
            // -------
            // DataFrame
            //     DataFrame containing SNPs with discrepant merge genotypes
            return $this->_discrepant_merge_genotypes;
        }
    
        public function getDiscrepantMergePositionsGenotypes(): DataFrame {
            // Get the DataFrame of SNPs with discrepant merge positions and genotypes.
            //
            // Returns
            // -------
            // DataFrame
            //     DataFrame containing SNPs with discrepant merge positions and genotypes
            $df = DataFrame::concat([$this->_discrepant_merge_positions, $this->_discrepant_merge_genotypes]);
    
            if (count($df) > 1) {
                $df = DataFrame::dropDuplicates($df);
            }
    
            return $df;
        }

        public function getBuild(): int
        {
            // Get the build number associated with the data.
            //
            // Returns
            // -------
            // int
            //     The build number
            return $this->_build;
        }
        
        public function isBuildDetected(): bool
        {
            // Check if the build was detected.
            //
            // Returns
            // -------
            // bool
            //     True if the build was detected, False otherwise
            return $this->_build_detected;
        }
        
        public function getAssembly(): string
        {
            // Get the assembly name based on the build number.
            //
            // Returns
            // -------
            // string
            //     The assembly name (e.g., "GRCh37", "NCBI36", "GRCh38")
            if ($this->_build === 37) {
                return "GRCh37";
            } elseif ($this->_build === 36) {
                return "NCBI36";
            } elseif ($this->_build === 38) {
                return "GRCh38";
            } else {
                return "";
            }
        }

        public function getCount(): int
        {
            // Replace with the relevant implementation for getting the count of SNPs.
            // For example: return count($this->_snps);
            //
            // Returns
            // -------
            // int
            //     The count of SNPs
            // TODO: Replace with the appropriate implementation
        }
    
        public function getChromosomes(): array
        {
            // Check if the "_snps" array is not empty
            if (!$this->_snps->isEmpty()) {
                // Return unique values of the "chrom" key in the "_snps" array
                return array_unique($this->_snps["chrom"]);
            } else {
                // Return an empty array if "_snps" is empty
                return [];
            }
        }

        public function getChromosomesSummary(): string
        {
            // Check if the "_snps" array is not empty
            if (!$this->_snps->isEmpty()) {
                // Get unique values of the "chrom" key in the "_snps" array
                $chroms = array_unique($this->_snps["chrom"]);
    
                // Separate integer and non-integer chromosomes
                $intChroms = array_filter($chroms, fn($chrom) => ctype_digit($chrom));
                $strChroms = array_filter($chroms, fn($chrom) => !ctype_digit($chrom));
    
                // Initialize an array to store ranges of integer chromosomes
                $intRanges = [];
    
                $start = null;
                $prev = null;
    
                // Sort the integer chromosomes in ascending order
                sort($intChroms);
    
                // Iterate over the sorted integer chromosomes
                foreach ($intChroms as $current) {
                    if ($start === null) {
                        // Set the start of a new range
                        $start = $current;
                    } else if ($prev !== null && $current !== $prev + 1) {
                        // If the current number is not consecutive to the previous one,
                        // add the range to the array
                        $intRanges[] = ($start === $prev) ? $start : "{$start}-{$prev}";
                        $start = $current;
                    }
                    $prev = $current;
                }
    
                // Add the last range (if any) to the array
                if ($start !== null) {
                    $intRanges[] = ($start === $prev) ? $start : "{$start}-{$prev}";
                }
    
                // Convert the ranges and non-integer chromosomes to strings
                $intChromsStr = implode(", ", $intRanges);
                $strChromsStr = implode(", ", $strChroms);
    
                if ($intChromsStr !== "" && $strChromsStr !== "") {
                    $intChromsStr .= ", ";
                }
    
                // Return the concatenated string representation of the chromosomes
                return $intChromsStr . $strChromsStr;
            } else {
                // Return an empty string if "_snps" is empty
                return "";
            }
        }
        
        public function getSex(): string
        {
            // Determine sex based on the presence of specific chromosomes
            $sex = $this->determineSex(chrom: "X");
            if (!$sex) {
                $sex = $this->determineSex(chrom: "Y");
            }
            // Return the determined sex
            return $sex;
        }
    
        public function filter(string $chrom = ''): array
        {
            // Implement the filtering logic here
            // Add your implementation code and comments here
            return [];
        }

        public function heterozygous(string $chrom = ''): array
        {
            // Call the filter() method to get the filtered data
            $df = $this->filter($chrom);
    
            // Filter the data to return rows with heterozygous genotypes
            return array_filter($df, function ($row) {
                return (
                    $row['genotype'] !== null &&                      // Genotype is not null
                    strlen($row['genotype']) === 2 &&                  // Genotype is of length 2
                    $row['genotype'][0] !== $row['genotype'][1]       // The two alleles are different
                );
            });
        }
    
        public function homozygous(string $chrom = ''): array
        {
            // Call the filter() method to get the filtered data
            $df = $this->filter($chrom);
    
            // Filter the data to return rows with homozygous genotypes
            return array_filter($df, function ($row) {
                return (
                    $row['genotype'] !== null &&                      // Genotype is not null
                    strlen($row['genotype']) === 2 &&                  // Genotype is of length 2
                    $row['genotype'][0] === $row['genotype'][1]        // The two alleles are the same
                );
            });
        }
    
        public function notnull(string $chrom = ''): array
        {
            // Call the filter() method to get the filtered data
            $df = $this->filter($chrom);
    
            // Filter the data to return rows with non-null genotypes
            return array_filter($df, function ($row) {
                return $row['genotype'] !== null;                     // Genotype is not null
            });
        }
        
        public function getSummary(): array
        {
            // Check if the object is valid
            if (!$this->isValid()) {
                return []; // If not valid, return an empty array
            } else {
                // If valid, return an array with summary information
                return [
                    "source" => $this->source,
                    "assembly" => $this->assembly,
                    "build" => $this->build,
                    "build_detected" => $this->build_detected,
                    "count" => $this->count,
                    "chromosomes" => $this->chromosomes_summary,
                    "sex" => $this->sex,
                ];
            }
        }
        
        public function isValid(): bool
        {
            // Check if the 'snps' property is empty
            if (empty($this->snps)) {
                return false; // If 'snps' is empty, the object is not valid
            } else {
                return true; // If 'snps' is not empty, the object is valid
            }
        }

        public function save(
            string $filename = "",
            bool $vcf = false,
            bool $atomic = true,
            string $vcf_alt_unavailable = ".",
            bool $vcf_qc_only = false,
            bool $vcf_qc_filter = false,
            array $kwargs = [] // For compatibility, changed **kwargs to array
        ): bool
        {
            // Trigger a deprecated error indicating that 'save' method should be replaced
            trigger_error(
                "Method 'save' has been replaced by 'to_csv', 'to_tsv', and 'to_vcf'.",
                E_USER_DEPRECATED
            );
        
            // Call the internal '_save' method with the provided arguments
            return $this->_save(
                $filename,
                $vcf,
                $atomic,
                $vcf_alt_unavailable,
                $vcf_qc_only,
                $vcf_qc_filter,
                $kwargs
            );
        }
        
        public function toCsv(string $filename = "", bool $atomic = true, array $options = []): string
        {
            // Set the separator to comma (",") in the options array
            $options["sep"] = ",";
            
            // Call the 'save' method with the provided arguments and return the result
            return $this->save($filename, $atomic, $options);
        }
        
        public function toTsv(string $filename = "", bool $atomic = true, array $options = []): string
        {
            // Set the separator to tab ("\t") in the options array
            $options["sep"] = "\t";
            
            // Call the 'save' method with the provided arguments and return the result
            return $this->save($filename, $atomic, $options);
        }
        
        public function to_vcf(
            string $filename = "",
            bool $atomic = true,
            string $alt_unavailable = ".",
            string $chrom_prefix = "",
            bool $qc_only = false,
            bool $qc_filter = false,
            array $kwargs = []
        ): string {
            // Output SNPs as Variant Call Format.
            //
            // Parameters:
            // $filename : str or buffer
            //     filename for file to save or buffer to write to
            // $atomic : bool
            //     atomically write output to a file on the local filesystem
            // $alt_unavailable : str
            //     representation of ALT allele when ALT is not able to be determined
            // $chrom_prefix : str
            //     prefix for chromosomes in VCF CHROM column
            // $qc_only : bool
            //     output only SNPs that pass quality control
            // $qc_filter : bool
            //     populate FILTER column based on quality control results
            // $kwargs : array
            //     additional parameters to pandas.DataFrame.to_csv
            //
            // Returns:
            // str
            //     path to file in output directory if SNPs were saved, else empty str
            //
            // Notes:
            // Parameters $qc_only and $qc_filter, if true, will identify low-quality SNPs per
            // "identify_low_quality_snps" method in the SNPs class if not done already.
            // Moreover, these parameters have no effect if this SNPs object does not map to a cluster
            // per "compute_cluster_overlap" method in the SNPs class.
            //
            // References:
            // 1. The Variant Call Format (VCF) Version 4.2 Specification, 8 Mar 2019,
            //    https://samtools.github.io/hts-specs/VCFv4.2.pdf
        
            return $this->_save(
                filename: $filename,
                vcf: true,
                atomic: $atomic,
                vcf_alt_unavailable: $alt_unavailable,
                vcf_chrom_prefix: $chrom_prefix,
                vcf_qc_only: $qc_only,
                vcf_qc_filter: $qc_filter,
                kwargs: $kwargs
            );
        }
        
        public function filter($chrom = "") {
            // Filter the snps collection based on the 'chrom' value
            return $chrom ? $this->snps->where('chrom', $chrom) : $this->snps;
        }
        
        public function read_raw_data($file, $only_detect_source, $rsids) {
            // Create a new instance of the Reader class
            $reader = new Reader($file, $only_detect_source, $this->resources, $rsids);
            // Read and return the data using the Reader instance
            return $reader->read();
        }
        
        public function assign_par_snps() {
            // Create a new instance of the EnsemblRestClient class with configuration options
            $rest_client = new EnsemblRestClient([
                'server' => 'https://api.ncbi.nlm.nih.gov',
                'reqs_per_sec' => 1,
            ]);
        
            // Iterate over the snps collection to find 'PAR' keys
            foreach ($this->snps->where('chrom', 'PAR')->keys() as $rsid) {
                if (strpos($rsid, 'rs') !== false) {
                    // Lookup the refsnp snapshot using the EnsemblRestClient
                    $response = $this->lookup_refsnp_snapshot($rsid, $rest_client);
        
                    if ($response !== null) {
                        // Iterate over the placements_with_allele in the response
                        foreach ($response['primary_snapshot_data']['placements_with_allele'] as $item) {
                            if (strpos($item['seq_id'], 'NC_000023') !== false) {
                                // Assign the snp with 'X' chromosome if seq_id contains 'NC_000023'
                                $assigned = $this->assign_snp($rsid, $item['alleles'], 'X');
                            } elseif (strpos($item['seq_id'], 'NC_000024') !== false) {
                                // Assign the snp with 'Y' chromosome if seq_id contains 'NC_000024'
                                $assigned = $this->assign_snp($rsid, $item['alleles'], 'Y');
                            } else {
                                $assigned = false;
                            }
        
                            if ($assigned) {
                                // Update the build if not already detected and break the loop
                                if (!$this->build_detected) {
                                    $this->build = $this->extract_build($item);
                                    $this->build_detected = true;
                                }
                                break;
                            }
                        }
                    }
                }
            }
        }

        private function lookupRefsnpSnapshot(string $rsid, RestClient $rest_client)
        {
            // Extract the ID from the rsid by removing the 'rs' prefix
            $id = substr($rsid, 2);
            // Perform the REST action to retrieve the RefSnp snapshot data
            $response = $rest_client->performRestAction("/variation/v0/refsnp/" . $id);
        
            if (isset($response["merged_snapshot_data"])) {
                // This RefSnp id was merged into another
                // We'll pick the first one to decide which chromosome this PAR will be assigned to
                $merged_id = "rs" . $response["merged_snapshot_data"]["merged_into"][0];
                $logger->info("SNP id {$rsid} has been merged into id {$merged_id}");
                // Recursively lookup the snapshot data for the merged id
                return $this->lookupRefsnpSnapshot($merged_id, $rest_client);
            } elseif (isset($response["nosnppos_snapshot_data"])) {
                $logger->warning("Unable to look up SNP id {$rsid}");
                return null;
            } else {
                // Return the response containing the snapshot data
                return $response;
            }
        }
        
        private function assignSnp(string $rsid, array $alleles, string $chrom)
        {
            // Only assign the SNP if positions match (i.e., same build)
            foreach ($alleles as $allele) {
                $allele_pos = $allele["allele"]["spdi"]["position"];
                // Ref SNP positions seem to be 0-based...
                if ($allele_pos == ($this->snps[$rsid]->pos - 1)) {
                    $this->snps[$rsid]->chrom = $chrom;
                    return true;
                }
            }
            return false;
        }
        
        private function extractBuild(array $item)
        {
            $assembly_name = $item["placement_annot"]["seq_id_traits_by_assembly"][0]["assembly_name"];
            // Extract the assembly name from the item
            $assembly_name = explode(".", $assembly_name)[0];
            // Extract the build number from the assembly name
            return (int)substr($assembly_name, -2);
        }

        public function detectBuild(): int
        {
            // Define a closure to look up the build based on SNP position
            $lookupBuildWithSnpPos = function ($pos, $s) {
                // Search for the position in the sorted collection and retrieve the corresponding value (build)
                return isset($s[$s->search($pos)]) ? (int)$s->keys()[$s->search($pos)] : 0;
            };
        
            $build = 0;
        
            // List of rsids to detect the build
            $rsids = [
                "rs3094315",
                "rs11928389",
                "rs2500347",
                "rs964481",
                "rs2341354",
                "rs3850290",
                "rs1329546",
            ];
        
            // Data frame with build positions for the rsids
            $df = [
                36 => [
                    742429,
                    50908372,
                    143649677,
                    27566744,
                    908436,
                    22315141,
                    135302086,
                ],
                37 => [
                    752566,
                    50927009,
                    144938320,
                    27656823,
                    918573,
                    23245301,
                    135474420,
                ],
                38 => [
                    817186,
                    50889578,
                    148946169,
                    27638706,
                    983193,
                    22776092,
                    136392261,
                ],
            ];
        
            foreach ($rsids as $rsid) {
                if (array_key_exists($rsid, $this->_snps)) {
                    // Create a collection from the data frame and map the values to SNP positions
                    $s = collect($df)->mapWithKeys(function ($value, $key) use ($rsid) {
                        return [$value[array_search($rsid, $rsids)] => $key];
                    });
        
                    // Look up the build based on the SNP position and the mapped collection
                    $build = $lookupBuildWithSnpPos($this->_snps[$rsid]['pos'], $s);
                }
        
                if ($build) {
                    // If the build is detected, break the loop
                    break;
                }
            }
        
            return $build;
        }
        
        public function get_count($chrom = "") {
            // Get the filtered snps collection based on the 'chrom' value
            $filteredSnps = $this->filter($chrom);
            // Return the count of the filtered snps collection
            return count($filteredSnps);
        }
        
        /**
         * Determine the sex based on X chromosome SNPs.
         *
         * @param float $threshold The threshold for determining sex.
         * @return string The determined sex ("Male", "Female") or an empty string if unknown.
         */
        public function determineSexX($threshold) {
            $x_snps = $this->getCount("X"); // Get the count of X chromosome SNPs

            if ($x_snps > 0) {
                if (count($this->heterozygous("X")) / $x_snps > $threshold) {
                    return "Female"; // More heterozygous SNPs than the threshold, likely female
                } else {
                    return "Male"; // Fewer heterozygous SNPs than the threshold, likely male
                }
            } else {
                return ""; // No X chromosome SNPs found
            }
        }

        /**
         * Determine the sex based on Y chromosome SNPs.
         *
         * @param float $threshold The threshold for determining sex.
         * @return string The determined sex ("Male", "Female") or an empty string if unknown.
         */
        public function determineSexY($threshold) {
            $y_snps = $this->getCount("Y"); // Get the count of Y chromosome SNPs

            if ($y_snps > 0) {
                if (count($this->notnull("Y")) / $y_snps > $threshold) {
                    return "Male"; // More non-null SNPs than the threshold, likely male
                } else {
                    return "Female"; // Fewer non-null SNPs than the threshold, likely female
                }
            } else {
                return ""; // No Y chromosome SNPs found
            }
        }
        
        public function deduplicateRsids()
        {
            // Keep the first duplicate rsid
            $duplicateRsids = $this->_snps->duplicated("first");
            // Save duplicate SNPs
            $this->_duplicate = array_merge($this->_duplicate, $this->_snps->where($duplicateRsids));
            // Deduplicate
            $this->_snps = $this->_snps->where(!$duplicateRsids);
        }    
    }

   /**
     * Get the start and stop positions of the non-PAR region on a given chromosome.
     *
     * @param string $chrom The chromosome identifier.
     * @return array An array containing the start and stop positions of the non-PAR region.
     */
    public function getNonParStartStop($chrom) {
        $pr = $this->getParRegions($this->build); // Get the PAR regions

        $np_start = $pr->filter(function ($item) use ($chrom) {
            return $item['chrom'] === $chrom && $item['region'] === "PAR1";
        })->pluck('stop')->first(); // Get the stop position of PAR1 on the given chromosome

        $np_stop = $pr->filter(function ($item) use ($chrom) {
            return $item['chrom'] === $chrom && $item['region'] === "PAR2";
        })->pluck('start')->first(); // Get the start position of PAR2 on the given chromosome

        return [$np_start, $np_stop]; // Return the start and stop positions of the non-PAR region
    }


    public function getNonParSnps($chrom, $heterozygous = true)
    {
        [$np_start, $np_stop] = $this->getNonParStartStop($chrom); // Get the start and stop positions of the non-PAR region
        $df = $this->filter($chrom); // Filter the data for the given chromosome

        if ($heterozygous) {
            // Get heterozygous SNPs in the non-PAR region (i.e., discrepant XY SNPs)
            return $df->filter(function ($row) use ($np_start, $np_stop) {
                return !is_null($row['genotype']) &&
                    strlen($row['genotype']) == 2 &&
                    $row['genotype'][0] != $row['genotype'][1] &&
                    $row['pos'] > $np_start &&
                    $row['pos'] < $np_stop;
            })->keys(); // Return the keys (indices) of the filtered SNPs
        } else {
            // Get homozygous SNPs in the non-PAR region
            return $df->filter(function ($row) use ($np_start, $np_stop) {
                return !is_null($row['genotype']) &&
                    strlen($row['genotype']) == 2 &&
                    $row['genotype'][0] == $row['genotype'][1] &&
                    $row['pos'] > $np_start &&
                    $row['pos'] < $np_stop;
            })->keys(); // Return the keys (indices) of the filtered SNPs
        }
    }

    public function deduplicateAlleles($rsids)
    {
        // Remove duplicate alleles
        $this->_snps = $this->_snps->map(function ($row) use ($rsids) {
            if (in_array($row["id"], $rsids)) {
                $row["genotype"] = $row["genotype"][0];
            }
            return $row;
        });
    }
    
    public function deduplicateSexChrom(string $chrom): void
    {
        // Deduplicate a chromosome in the non-PAR region.
        $discrepantXYSnps = $this->getNonParSnps($chrom);
    
        // Save discrepant XY SNPs
        $this->_discrepant_XY = array_merge(
            $this->_discrepant_XY,
            $this->_snps[$discrepantXYSnps]
        );
    
        // Drop discrepant XY SNPs since it's ambiguous for which allele to deduplicate
        unset($this->_snps[$discrepantXYSnps]);
    
        // Get remaining non-PAR SNPs with two alleles
        $nonParSnps = $this->getNonParSnps($chrom, heterozygous: false);
    
        // Deduplicate the remaining non-PAR SNPs
        $this->deduplicateAlleles($nonParSnps);
    }
    
    public function deduplicateXYChrom(): void
    {
        // Fix chromosome issue where some data providers duplicate male X and Y chromosomes
        $this->deduplicateSexChrom("X");
        $this->deduplicateSexChrom("Y");
    }        

    public function deduplicateMTChrom(): void
    {
        // Deduplicate MT chromosome.
        $heterozygousMTSnps = $this->_snps[$this->heterozygous("MT")->index] ?? [];
    
        // Save heterozygous MT SNPs
        $this->_heterozygous_MT = array_merge(
            $this->_heterozygous_MT,
            $this->_snps[$heterozygousMTSnps]
        );
    
        // Drop heterozygous MT SNPs since it's ambiguous for which allele to deduplicate
        unset($this->_snps[$heterozygousMTSnps]);
    
        $this->deduplicateAlleles($this->homozygous("MT")->index);
    }

    function get_par_regions(int $build): array {
        $data = [];
        if ($build === 37) {
            $data = [
                ["region" => "PAR1", "chrom" => "X", "start" => 60001, "stop" => 2699520],
                ["region" => "PAR2", "chrom" => "X", "start" => 154931044, "stop" => 155260560],
                ["region" => "PAR1", "chrom" => "Y", "start" => 10001, "stop" => 2649520],
                ["region" => "PAR2", "chrom" => "Y", "start" => 59034050, "stop" => 59363566],
            ];
        } elseif ($build === 38) {
            $data = [
                ["region" => "PAR1", "chrom" => "X", "start" => 10001, "stop" => 2781479],
                ["region" => "PAR2", "chrom" => "X", "start" => 155701383, "stop" => 156030895],
                ["region" => "PAR1", "chrom" => "Y", "start" => 10001, "stop" => 2781479],
                ["region" => "PAR2", "chrom" => "Y", "start" => 56887903, "stop" => 57217415],
            ];
        } elseif ($build === 36) {
            $data = [
                ["region" => "PAR1", "chrom" => "X", "start" => 1, "stop" => 2709520],
                ["region" => "PAR2", "chrom" => "X", "start" => 154584238, "stop" => 154913754],
                ["region" => "PAR1", "chrom" => "Y", "start" => 1, "stop" => 2709520],
                ["region" => "PAR2", "chrom" => "Y", "start" => 57443438, "stop" => 57772954],
            ];
        }
        return $data;
    }
    
    function natural_sort_key(string $value): array {
        return preg_split("/(\D*\d+\D*)/", $value, 0, PREG_SPLIT_DELIM_CAPTURE);
    }
    
    function sort_snps(array &$snps): void {
        // Get unique chrom values
        $uniqueChromosomalValues = array_unique(array_column($snps, 'chrom'));
    
        // Sort uniqueChromosomalValues based on natural sorting
        usort($uniqueChromosomalValues, function ($a, $b) {
            return strnatcmp($a, $b);
        });
    
        // Move PAR and MT to the end of sorted array
        if (($key = array_search("PAR", $uniqueChromosomalValues)) !== false) {
            unset($uniqueChromosomalValues[$key]);
            $uniqueChromosomalValues[] = "PAR";
        }
        if (($key = array_search("MT", $uniqueChromosomalValues)) !== false) {
            unset($uniqueChromosomalValues[$key]);
            $uniqueChromosomalValues[] = "MT";
        }
    
        // Sort snps based on uniqueChromosomalValues and pos
        usort($snps, function ($a, $b) use ($uniqueChromosomalValues) {
            $chromosomeA = array_search($a['chrom'], $uniqueChromosomalValues);
            $chromosomeB = array_search($b['chrom'], $uniqueChromosomalValues);
    
            if ($chromosomeA === $chromosomeB) {
                return $a['pos'] <=> $b['pos'];
            }
            return $chromosomeA <=> $chromosomeB;
        });
    }
    
    public function remap(string $target_assembly, bool $complement_bases = true): array
    {
        $chromosomes_remapped = [];
        $chromosomes_not_remapped = [];
        $snps = $this->snps;
    
        // Check if there are SNPs to remap
        if ($snps->empty) {
            logger.warning("No SNPs to remap");
            return [$chromosomes_remapped, $chromosomes_not_remapped];
        } else {
            $chromosomes = $snps["chrom"]->unique();
            $chromosomes_not_remapped = $chromosomes->values();
    
            $valid_assemblies = ["NCBI36", "GRCh37", "GRCh38", 36, 37, 38];
    
            // Validate target assembly
            if (!in_array($target_assembly, $valid_assemblies, true)) {
                logger.warning("Invalid target assembly");
                return [$chromosomes_remapped, $chromosomes_not_remapped];
            }
    
            // Convert target assembly to string format
            if (is_int($target_assembly)) {
                if ($target_assembly == 36) {
                    $target_assembly = "NCBI36";
                } else {
                    $target_assembly = "GRCh" . strval($target_assembly);
                }
            }
    
            // Determine source assembly based on current build
            if ($this->build == 36) {
                $source_assembly = "NCBI36";
            } else {
                $source_assembly = "GRCh" . strval($this->build);
            }
    
            // Check if source and target assemblies are the same
            if ($source_assembly === $target_assembly) {
                return [$chromosomes_remapped, $chromosomes_not_remapped];
            }
    
            // Get assembly mapping data
            $assembly_mapping_data = $this->_resources->get_assembly_mapping_data($source_assembly, $target_assembly);
            if (!$assembly_mapping_data) {
                return [$chromosomes_remapped, $chromosomes_not_remapped];
            }
    
            $tasks = [];
            foreach ($chromosomes as $chrom) {
                if (array_key_exists($chrom, $assembly_mapping_data)) {
                    $chromosomes_remapped[] = $chrom;
                    unset($chromosomes_not_remapped[array_search($chrom, $chromosomes_not_remapped)]);
                    $mappings = $assembly_mapping_data[$chrom];
    
                    // Prepare remapping task
                    $tasks[] = [
                        "snps" => $snps->loc[$snps["chrom"] === $chrom],
                        "mappings" => $mappings,
                        "complement_bases" => $complement_bases,
                    ];
                } else {
                    // Chromosome not remapped, remove it from SNPs for consistency
                    logger.warning("Chromosome {$chrom} not remapped; removing chromosome from SNPs for consistency");
                    $snps = $snps->drop($snps->loc[$snps["chrom"] === $chrom]->index);
                }
            }
    
            // Perform remapping in parallel
            $remapped_snps = $this->_parallelizer($this->_remapper, $tasks);
            $remapped_snps = pd.concat($remapped_snps);
    
            // Update remapped SNP positions and genotypes
            $snps->loc[$remapped_snps->index, "pos"] = $remapped_snps["pos"];
            $snps->loc[$remapped_snps->index, "genotype"] = $remapped_snps["genotype"];
            $snps->pos = $snps->pos->astype(np.uint32);
    
            // Update SNPs and rebuild index
            $this->_snps = $snps;
            $this->sort();
    
            // Update the build with the target assembly
            $this->_build = intval(substr($target_assembly, -2));
    
            return [$chromosomes_remapped, $chromosomes_not_remapped];
        }
    }
    
    private function _remapper(array $task): DataFrame
    {
        $temp = $task["snps"]->copy();
        $mappings = $task["mappings"];
        $complement_bases = $task["complement_bases"];
    
        $temp["remapped"] = false;
    
        $pos_start = intval($temp["pos"]->describe()["min"]);
        $pos_end = intval($temp["pos"]->describe()["max"]);
    
        foreach ($mappings["mappings"] as $mapping) {
            $orig_start = $mapping["original"]["start"];
            $orig_end = $mapping["original"]["end"];
            $mapped_start = $mapping["mapped"]["start"];
            $mapped_end = $mapping["mapped"]["end"];
    
            $orig_region = $mapping["original"]["seq_region_name"];
            $mapped_region = $mapping["mapped"]["seq_region_name"];
    
            // Skip if mapping is outside of range of SNP positions
            if ($orig_end < $pos_start || $orig_start > $pos_end) {
                continue;
            }
    
            // Find the SNPs that are being remapped for this mapping
            $snp_indices = $temp->loc[
                !$temp["remapped"]
                & ($temp["pos"] >= $orig_start)
                & ($temp["pos"] <= $orig_end)
            ]->index;
    
            // If there are no SNPs here, skip
            if (count($snp_indices) === 0) {
                continue;
            }
    
            $orig_range_len = $orig_end - $orig_start;
            $mapped_range_len = $mapped_end - $mapped_start;
    
            // If this would change chromosome, skip
            // TODO: Allow within normal chromosomes
            // TODO: Flatten patches
            if ($orig_region !== $mapped_region) {
                logger.warning(
                    "discrepant chroms for " . count($snp_indices) . " SNPs from " . $orig_region . " to " . $mapped_region
                );
                continue;
            }
    
            // If there is any stretching or squashing of the region
            // observed when mapping NCBI36 -> GRCh38
            // TODO: Disallow skipping a version when remapping
            if ($orig_range_len !== $mapped_range_len) {
                logger.warning(
                    "discrepant coords for " . count($snp_indices) . " SNPs from " . $orig_region . ":" . $orig_start . "-" . $orig_end . " to " . $mapped_region . ":" . $mapped_start . "-" . $mapped_end
                );
                continue;
            }
    
            // Remap the SNPs
            if ($mapping["mapped"]["strand"] === -1) {
                // Flip and (optionally) complement since we're mapping to the minus strand
                $diff_from_start = $temp->loc[$snp_indices, "pos"] - $orig_start;
                $temp->loc[$snp_indices, "pos"] = $mapped_end - $diff_from_start;
    
                if ($complement_bases) {
                    $temp->loc[$snp_indices, "genotype"] = $temp->loc[$snp_indices, "genotype"]->apply($this->_complement_bases);
                }
            } else {
                // Mapping is on the same (plus) strand, so just remap based on offset
                $offset = $mapped_start - $orig_start;
                $temp->loc[$snp_indices, "pos"] = $temp["pos"] + $offset;
            }
    
            // Mark these SNPs as remapped
            $temp->loc[$snp_indices, "remapped"] = true;
        }
    
        return $temp;
    }
    
    /**
     * Returns the complement of a given genotype string.
     *
     * @param string|null $genotype The genotype string to complement.
     * @return string|null The complement of the genotype string, or null if the input is null.
     */
    function complement_bases(string|null $genotype): string|null
    {
        if (is_null($genotype)) {
            return null;
        }

        $complement = ""; // Variable to store the complement genotype string.

        // Iterate over each character in the genotype string.
        for ($i = 0; $i < strlen($genotype); $i++) {
            $base = $genotype[$i]; // Get the current base.

            // Determine the complement of the base and append it to the complement string.
            if ($base === "A") {
                $complement .= "T";
            } elseif ($base === "G") {
                $complement .= "C";
            } elseif ($base === "C") {
                $complement .= "G";
            } elseif ($base === "T") {
                $complement .= "A";
            } else {
                $complement .= $base; // If the base is not A, G, C, or T, keep it as is.
            }
        }

        return $complement; // Return the complement genotype string.
    }
    
    /**
     * Returns an array representing the natural sort order of a given string.
     *
     * @param string $s The string to generate the natural sort key for.
     * @return array An array representing the natural sort order of the string.
     */
    function natural_sort_key(string $s): array
    {
        $natural_sort_re = '/([0-9]+)/'; // Regular expression pattern to match numbers in the string.

        // Split the string using the regular expression pattern and capture the delimiter.
        // Map each segment to its corresponding natural sort key value.
        return array_map(
            fn($text) => is_numeric($text) ? intval($text) : strtolower($text),
            preg_split($natural_sort_re, $s, -1, PREG_SPLIT_DELIM_CAPTURE)
        );
    }
    
    
?>
