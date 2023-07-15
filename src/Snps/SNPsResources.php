<?php

/**
 * php-dna.
 *
 * tools for genetic genealogy and the analysis of consumer DNA test results
 *
 * @author          Devmanateam <devmanateam@outlook.com>
 * @copyright       Copyright (c) 2020-2023, Devmanateam
 * @license         MIT
 *
 * @link            http://github.com/familytree365/php-dna
 */

namespace Dna\Snps;

use Dna\Snps\IO\CsvReader;
use Exception;
use PharData;
use Phar;
use League\Csv\Reader;
use League\Csv\Statement;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;
use GuzzleHttp\Client;
use GuzzleHttp\GuzzleException;

/**
 * Class SNPsResources.
 */
class SNPsResources extends Singleton
{
    /**
     * The directory where the resources are located
     * @var string
     */
    private string $_resources_dir;

    /**
     * The Ensembl REST client used to retrieve resources
     * @var EnsemblRestClient
     */
    private EnsemblRestClient $_ensembl_rest_client;



    private $logger = null;

    /**
     * Constructor for the ResourceManager class
     * @param string $resources_dir The directory where the resources are located
     */

    public function __construct(
        string $resources_dir = "resources",
        private Client $httpClient = new Client()
    ) {
        $this->_resources_dir = realpath($resources_dir);
        $this->_ensembl_rest_client = new EnsemblRestClient();
        $this->init_resource_attributes();
    }

    public function getResourcesDir(): string
    {
        return $this->_resources_dir;
    }

    public function setResourcesDir(string $resources_dir): void
    {
        $this->_resources_dir = $resources_dir;
    }

    public function getHttpClient(): Client
    {
        return $this->httpClient;
    }

    public function setHttpClient(Client $httpClient): void
    {
        $this->httpClient = $httpClient;
    }

    public function setRestClient($rest_client): void
    {
        $this->_ensembl_rest_client = $rest_client;
    }

    /**
     * An array of reference sequences
     * @var array
     */
    private array $_reference_sequences;

    /**
     * A map of GSA RSIDs to chromosome positions
     * @var array|null
     */
    private ?array $_gsa_rsid_map;

    /**
     * A map of GSA chromosome positions to RSIDs
     * @var array|null
     */
    private ?array $_gsa_chrpos_map;

    /**
     * A map of dbSNP 151 to GRCh37 reverse mappings
     * @var array|null
     */
    private ?array $_dbsnp_151_37_reverse;

    /**
     * An array of filenames for the OpenSNP datadump
     * @var array
     */
    private array $_opensnp_datadump_filenames;

    /**
     * A map of chip clusters
     * @var array|null
     */
    private ?array $_chip_clusters;

    /**
     * An array of low quality SNPs
     * @var array|null
     */
    private ?array $_low_quality_snps;

    /**
     * Initializes the resource attributes
     */
    public function init_resource_attributes(): void
    {
        $this->_reference_sequences = [];
        $this->_gsa_rsid_map = null;
        $this->_gsa_chrpos_map = null;
        $this->_dbsnp_151_37_reverse = null;
        $this->_opensnp_datadump_filenames = [];
        $this->_chip_clusters = null;
        $this->_low_quality_snps = null;
    }

    /**
     * An array of reference sequences
     * @var array
     */
    private $referenceSequences = [];

    /**
     * Retrieves reference sequences for the specified assembly and chromosomes
     * @param string $assembly The assembly to retrieve reference sequences for
     * @param array $chroms The chromosomes to retrieve reference sequences for
     * @return array An array of reference sequences
     */
    public function getReferenceSequences(
        string $assembly = "GRCh37",
        array $chroms = [
            "1", "2", "3", "4", "5", "6", "7", "8", "9", "10",
            "11", "12", "13", "14", "15", "16", "17", "18", "19", "20",
            "21", "22", "X", "Y", "MT",
        ]
    ): array {
        $validAssemblies = ["NCBI36", "GRCh37", "GRCh38"];

        if (!in_array($assembly, $validAssemblies)) {
            error_log("Invalid assembly");
            return [];
        }

        if (!$this->referenceChromsAvailable($assembly, $chroms)) {
            $this->referenceSequences[$assembly] = $this->createReferenceSequences(
                ...$this->getPathsReferenceSequences(assembly: $assembly, chroms: $chroms)
            );
        }

        return $this->referenceSequences[$assembly];
    }

    /**
     * Checks if reference chromosomes are available for the specified assembly and chromosomes
     * @param string $assembly The assembly to check reference chromosomes for
     * @param array $chroms The chromosomes to check reference chromosomes for
     * @return bool True if reference chromosomes are available, false otherwise
     */
    private function referenceChromsAvailable(string $assembly, array $chroms): bool
    {
        // TODO: Implement reference chromosome availability check
        return false;
    }

    /**
     * Creates reference sequences from the specified paths
     * @param string $fastaPath The path to the FASTA file
     * @param string $faiPath The path to the FAI file
     * @param string $dictPath The path to the dictionary file
     * @return array An array of reference sequences
     */
    private function createReferenceSequences(string $fastaPath, string $faiPath, string $dictPath): array
    {
        // TODO: Implement reference sequence creation
        return [];
    }

    // /**
    //  * Retrieves paths to reference sequences for the specified assembly and chromosomes
    //  * @param string $assembly The assembly to retrieve reference sequence paths for
    //  * @param array $chroms The chromosomes to retrieve reference sequence paths for
    //  * @return array An array of paths to reference sequences
    //  */
    // private function getPathsReferenceSequences(string $assembly, array $chroms): array {
    //     // TODO: Implement reference sequence path retrieval
    //     return [];
    // }

    /**
     * Get assembly mapping data.
     *
     * @param string $sourceAssembly {'NCBI36', 'GRCh37', 'GRCh38'} assembly to remap from
     * @param string $targetAssembly {'NCBI36', 'GRCh37', 'GRCh38'} assembly to remap to
     *
     * @return array array of json assembly mapping data if loading was successful, else []
     */
    public function getAssemblyMappingData(string $sourceAssembly, string $targetAssembly): array
    {
        // Get assembly mapping data.
        return $this->loadAssemblyMappingData(
            $this->getPathAssemblyMappingData($sourceAssembly, $targetAssembly)
        );
    }

    /**
     * Downloads example datasets.
     *
     * @return array Array of downloaded file paths.
     */
    public function download_example_datasets(): array
    {
        $paths = [];

        // Download 23andMe example dataset.
        $paths[] = $this->download_file("https://opensnp.org/data/662.23andme.340", "662.23andme.340.txt.gz", true);

        // Download FTDNA Illumina example dataset.
        $paths[] = $this->download_file("https://opensnp.org/data/662.ftdna-illumina.341", "662.ftdna-illumina.341.csv.gz", true);

        return $paths;
    }

    /**
     * Gets / downloads all resources used throughout snps.
     *
     * @return array Array of resources.
     */
    public function getAllResources()
    {
        // Get / download all resources used throughout snps.
        //
        // Notes
        // -----
        // This function does not download reference sequences and the openSNP datadump,
        // due to their large sizes.
        //
        // Returns
        // -------
        // array of resources

        $resources = [];
        $versions = ["NCBI36", "GRCh37", "GRCh38"];

        // Loop through all possible assembly mappings and get their data.
        for ($i = 0; $i < count($versions); ++$i) {
            for ($j = 0; $j < count($versions); ++$j) {
                if ($i === $j) {
                    continue;
                }
                $source = $versions[$i];
                $target = $versions[$j];
                $resources[$source . "_" . $target] = $this->getAssemblyMappingData($source, $target);
            }
        }

        // Get GSA resources.
        $resources["gsa_resources"] = $this->getGsaResources();
        // Get chip clusters.
        $resources["chip_clusters"] = $this->get_chip_clusters();
        // Get low quality SNPs.
        $resources["low_quality_snps"] = $this->getLowQualitySnps();
        return $resources;
    }

    /**
     * Gets Homo sapiens reference sequences for Builds 36, 37, and 38 from Ensembl.
     *
     * @param mixed ...$args Additional arguments to pass to getReferenceSequences.
     *
     * @return array Dictionary of ReferenceSequence, else {}.
     */
    public function getAllReferenceSequences(...$args): array
    {
        /**
         * Get Homo sapiens reference sequences for Builds 36, 37, and 38 from Ensembl.
         *
         * Notes
         * -----
         * This function can download over 2..
         *
         * Returns
         * -------
         * dict
         *   dict of ReferenceSequence, else {}
         */

        $assemblies = ["NCBI36", "GRCh37", "GRCh38"];

        // Loop through all assemblies and get their reference sequences.
        foreach ($assemblies as $assembly) {
            $this->getReferenceSequences($assembly, ...$args);
        }

        return $this->_reference_sequences;
    }

    /**
     * Get resources for reading Global Screening Array files.
     * https://support.illumina.com/downloads/infinium-global-screening-array-v2-0-product-files.html
     *
     * @return array An array of resources for reading Global Screening Array files.
     */
    public function getGsaResources(): array
    {
        // Get the rsid map resource.
        $rsid_map = $this->getGsaRsid();

        // Get the chrpos map resource.
        $chrpos_map = $this->getGsaChrpos();

        // Get the dbsnp 151 37 reverse resource.
        $dbsnp_151_37_reverse = $this->get_dbsnp_151_37_reverse();

        // Return an array of resources.
        return [
            "rsid_map" => $rsid_map,
            "chrpos_map" => $chrpos_map,
            "dbsnp_151_37_reverse" => $dbsnp_151_37_reverse,
        ];
    }

    /**
     * Get resource for identifying deduced genotype / chip array based on chip clusters.
     *
     * @return array
     *
     * @references
     *  - Chang Lu, Bastian Greshake Tzovaras, Julian Gough, A survey of
     *    direct-to-consumer genotype data, and quality control tool
     *    (GenomePrep) for research, Computational and Structural
     *    Biotechnology Journal, Volume 19, 2021, Pages 3747-3754, ISSN
     *    2001-0370, https://doi.org/10.1016/j.csbj.2021.06.040.
     */
    public function get_chip_clusters()
    {
        if ($this->_chip_clusters === null) {
            $chip_clusters_path = $this->download_file(
                "https://supfam.mrc-lmb.cam.ac.uk/GenomePrep/datadir/the_list.tsv.gz",
                "chip_clusters.tsv.gz"
            );

            $csv = Reader::createFromPath($chip_clusters_path, 'r');
            $csv->setDelimiter("\t");

            $stmt = (new Statement())->offset(0)->limit(1);
            $header = $stmt->process($csv)->getHeader();

            $stmt = new Statement();
            $chip_clusters = [];

            foreach ($stmt->process($csv) as $row) {
                $locus = $row[0] ?? ":";
                $cluster = $row[1] ?? "";

                [$chrom, $pos] = array_pad(explode(':', $locus), 2, '');
                $pos = (int) $pos;

                $chip_clusters[] = [
                    'chrom' => $chrom,
                    'pos' => $pos,
                    'clusters' => $cluster,
                ];
            }

            $this->_chip_clusters = $chip_clusters;
        }

        return $this->_chip_clusters;
    }

    /**
     * Get the low quality SNPs data.
     *
     * @return array The low quality SNPs data.
     */
    private ?array $_lowQualitySnps = null;

    public function getLowQualitySNPs(): array
    {
        // If the low quality SNPs data has not been loaded yet, download and process it.
        if ($this->_lowQualitySnps === null) {
            // Download the low quality SNPs file.
            $lowQualitySnpsPath = $this->download_file(
                "https://supfam.mrc-lmb.cam.ac.uk/GenomePrep/datadir/badalleles.tsv.gz",
                "low_quality_snps.tsv.gz"
            );

            // Load the low quality SNPs file into an array.
            $fileContents = file_get_contents($lowQualitySnpsPath);

            // Check if the file is gzipped
            if (substr($fileContents, 0, 2) === "\x1f\x8b") {
                $fileContents = gzdecode($fileContents);
            }

            $rows = explode("\n", $fileContents);

            // Process the low quality SNPs data into an array of arrays.
            $clusterDfs = [];

            foreach ($rows as $row) {
                if (empty($row)) {
                    continue;
                }

                [$cluster, $loci] = array_pad(explode("\t", $row), 2, '');
                $lociSplit = explode(",", $loci);

                foreach ($lociSplit as $locus) {
                    $clusterDfs[] = ['cluster' => $cluster, 'locus' => $locus];
                }
            }

            // Transform the low quality SNPs data into an array of arrays with separate columns for chromosome and position.
            $transformedData = [];

            foreach ($clusterDfs as $clusterDf) {
                [$chrom, $pos] = array_pad(explode(':', $clusterDf['locus']), 2, '');
                $transformedData[] = [
                    'cluster' => $clusterDf['cluster'],
                    'chrom' => $chrom,
                    'pos' => intval($pos)
                ];
            }

            // Save the processed low quality SNPs data to the object.
            $this->_lowQualitySnps = $transformedData;
        }

        // Return the low quality SNPs data.
        return $this->_lowQualitySnps;
    }

    /**
     * Get and load RSIDs that are on the reference reverse (-) strand in dbSNP 151 and lower.
     *
     * @return array An array of RSIDs that are on the reference reverse (-) strand in dbSNP 151 and lower.
     *
     * References:
     * 1. Sherry ST, Ward MH, Kholodov M, Baker J, Phan L, Smigielski EM, Sirotkin K.
     *    dbSNP: the NCBI database of genetic variation. Nucleic Acids Res. 2001 Jan 1;
     *    29(1):308-11.
     * 2. Database of Single Nucleotide Polymorphisms (dbSNP). Bethesda (MD): National Center
     *    for Biotechnology Information, National Library of Medicine. (dbSNP Build ID: 151).
     *    Available from: http://www.ncbi.nlm.nih.gov/SNP/
     */
    public function get_dbsnp_151_37_reverse(): ?array
    {
        // If the dbsnp 151 37 reverse data has not been loaded yet, download and process it.
        if ($this->_dbsnp_151_37_reverse === null) {
            // download the file from the cloud, if not done already
            $dbsnp_rev_path = $this->download_file(
                "https://sano-public.s3.eu-west-2.amazonaws.com/dbsnp151.b37.snps_reverse.txt.gz",
                "dbsnp_151_37_reverse.txt.gz"
            );

            $fileContents = file_get_contents($dbsnp_rev_path);

            // Check if the file is gzipped
            if (substr($fileContents, 0, 2) === "\x1f\x8b") {
                $fileContents = gzdecode($fileContents);
            }

            // load into pandas
            $header = array(
                "dbsnp151revrsid",
                "dbsnp151freqa",
                "dbsnp151freqt",
                "dbsnp151freqc",
                "dbsnp151freqg"
            );

            $rsids = array();
            $lines = explode("\n", $fileContents);
            foreach ($lines as $line) {
                if (!empty($line) && $line[0] !== "#") {  // skip the first row
                    $row = explode(" ", trim($line));
                    $rsid = array(
                        "dbsnp151revrsid" => isset($row[0]) ? $row[0] : '',
                        "dbsnp151freqa" => isset($row[1]) ? (float)$row[1] : 0.0,
                        "dbsnp151freqt" => isset($row[2]) ? (float)$row[2] : 0.0,
                        "dbsnp151freqc" => isset($row[3]) ? (float)$row[3] : 0.0,
                        "dbsnp151freqg" => isset($row[4]) ? (float)$row[4] : 0.0
                    );
                    $rsids[] = $rsid;
                }
            }

            // store in memory so we don't have to load again
            $this->_dbsnp_151_37_reverse = $rsids;
        }

        return $this->_dbsnp_151_37_reverse;
    }

    /**
     * Get the filenames of the OpenSNP datadump files.
     *
     * @return array The filenames of the OpenSNP datadump files.
     */
    public function getOpensnpDatadumpFilenames(): array
    {
        // If the OpenSNP datadump filenames have not been loaded yet, load them from the path.
        if (!$this->opensnpDatadumpFilenames) {
            $this->opensnpDatadumpFilenames = $this->getOpensnpDatadumpFilenamesFromPath(
                $this->getPathOpensnpDatadump()
            );
        }
        // Return the OpenSNP datadump filenames.
        return $this->opensnpDatadumpFilenames;
    }

    /**
     * Write data to a gzip file.
     *
     * @param string $filename The name of the gzip file to write to.
     * @param string $data The data to write to the gzip file.
     */
    function writeDataToGzip(string $filename, string $data)
    {
        // Open the gzip file for writing.
        $fGzip = gzopen($filename, 'wb');

        // Write the data to the gzip file.
        gzwrite($fGzip, $data);

        // Close the gzip file.
        gzclose($fGzip);
    }

    /**
     * Load assembly mapping data from a tar file.
     *
     * @param string $filename The name of the tar file to load the assembly mapping data from.
     * @return array The assembly mapping data.
     */
    public static function loadAssemblyMappingData(string $filename): array
    {
        // Initialize an empty array to store the assembly mapping data.
        $assembly_mapping_data = [];

        // Create a new PharData object from the tar file.
        $tar = new PharData($filename);

        // Iterate over each file in the tar file.
        foreach ($tar as $tarinfo) {
            $member_name = $tarinfo->getFilename();

            // If the file is a JSON file, load its contents into an array and add it to the assembly mapping data.
            if (str_contains($member_name, ".json")) {
                $tarfile = file_get_contents($tarinfo->getPathname());
                $tar_bytes = $tarfile;
                $assembly_mapping_data[explode(".", $member_name)[0]] = json_decode($tar_bytes, true);
            }
        }

        // Return the assembly mapping data.
        return $assembly_mapping_data;
    }

    /**
     * Get the paths to reference sequences for a given assembly and set of chromosomes.
     *
     * @param string $sub_dir The subdirectory to download the reference sequences to.
     * @param string $assembly The assembly to get the reference sequences for.
     * @param array $chroms The chromosomes to get the reference sequences for.
     * @return array An array containing the assembly, chromosomes, URLs, and local filenames of the reference sequences.
     */
    public function getPathsReferenceSequences(
        string $sub_dir = "fasta",
        string $assembly = "GRCh37",
        array $chroms = []
    ): array {
        // Determine the base URL and release number based on the assembly.
        if ($assembly === "GRCh37") {
            $base = "ftp://ftp.ensembl.org/pub/grch37/release-96/fasta/homo_sapiens/dna/";
            $release = "";
        } elseif ($assembly === "NCBI36") {
            $base = "ftp://ftp.ensembl.org/pub/release-54/fasta/homo_sapiens/dna/";
            $release = "54.";
        } elseif ($assembly === "GRCh38") {
            $base = "ftp://ftp.ensembl.org/pub/release-96/fasta/homo_sapiens/dna/";
            $release = "";
        } else {
            // If the assembly is not recognized, return an empty array.
            return ["", [], [], []];
        }

        // Generate the filenames, URLs, and local filenames for the reference sequences.
        $filenames = array_map(
            fn ($chrom) => "Homo_sapiens.{$assembly}.{$release}dna.chromosome.{$chrom}.fa.gz",
            $chroms
        );

        $urls = array_map(fn ($filename) => "{$base}{$filename}", $filenames);

        $local_filenames = array_map(
            fn ($filename) => "{$sub_dir}" . DIRECTORY_SEPARATOR . "{$assembly}" . DIRECTORY_SEPARATOR . "{$filename}",
            $filenames
        );

        // Download the reference sequences and return the assembly, chromosomes, URLs, and local filenames.
        $downloads = array_map(function ($url, $localFilename) {
            return $this->download_file($url, $localFilename);
        }, $urls, $local_filenames);

        return [$assembly, $chroms, $urls, $downloads];
    }

    /**
     * Create reference sequences from downloaded files.
     *
     * @param string $assembly The assembly of the reference sequences.
     * @param array $chroms The chromosomes of the reference sequences.
     * @param array $urls The URLs of the reference sequences.
     * @param array $paths The paths of the downloaded reference sequence files.
     * @return array An array of ReferenceSequence objects.
     */
    public function create_reference_sequences($assembly, $chroms, $urls, $paths)
    {
        // Initialize an empty array to store the reference sequences.
        $seqs = [];

        // Iterate over each downloaded reference sequence file.
        foreach ($paths as $i => $path) {
            // If the path is empty, skip this reference sequence.
            if (!$path) {
                continue;
            }

            // Create a dictionary to store information about the reference sequence.
            $d = [];

            // Add the chromosome, URL, path, assembly, species, and taxonomy to the dictionary.
            $d["ID"] = $chroms[$i];
            $d["url"] = $urls[$i];
            $d["path"] = realpath($path);
            $d["assembly"] = $assembly;
            $d["species"] = "Homo sapiens";
            $d["taxonomy"] = "x";

            // Create a new ReferenceSequence object from the dictionary and add it to the array of reference sequences.
            $seqs[$chroms[$i]] = new ReferenceSequence($d);
        }

        // Return the array of reference sequences.
        return $seqs;
    }

    /**
     * Get the path to the assembly mapping data for a given source and target assembly.
     *
     * @param string $source_assembly The source assembly of the assembly mapping data.
     * @param string $target_assembly The target assembly of the assembly mapping data.
     * @param int $retries The number of times to retry downloading the assembly mapping data if it fails.
     * @return string The path to the assembly mapping data.
     */
    public function getPathAssemblyMappingData(string $source_assembly, string $target_assembly, int $retries = 10): string
    {
        // Create the resources directory if it does not exist.
        if (!$this->create_dir($this->_resources_dir)) {
            return "";
        }

        // Generate the list of chromosomes to download the assembly mapping data for.
        $chroms = [];
        for ($i = 1; $i <= 22; $i++) {
            $chroms[] = (string)$i;
        }
        $chroms = array_merge($chroms, ["X", "Y", "MT"]);

        // Generate the name of the assembly mapping data file and its destination path.
        $assembly_mapping_data = $source_assembly . "_" . $target_assembly;
        $destination = $this->_resources_dir . DIRECTORY_SEPARATOR . $assembly_mapping_data . ".tar.gz";

        // If the assembly mapping data file does not exist, download it.
        if (!file_exists($destination)) {
            if ($this->logger) {
                $this->logger->info("Downloading " . str_replace(getcwd(), ".", $destination));
            }
            $this->downloadAssemblyMappingData($destination, $chroms, $source_assembly, $target_assembly, $retries);
        }

        // Return the path to the assembly mapping data file.
        return $destination;
    }

    /**
     * Download assembly mapping data for a given set of chromosomes and save it to a tar file.
     *
     * @param string $destination The destination path of the tar file to save the assembly mapping data to.
     * @param array $chroms The chromosomes to download the assembly mapping data for.
     * @param string $sourceAssembly The source assembly of the assembly mapping data.
     * @param string $targetAssembly The target assembly of the assembly mapping data.
     * @param int $retries The number of times to retry downloading the assembly mapping data if it fails.
     */
    public function downloadAssemblyMappingData($destination, $chroms, $sourceAssembly, $targetAssembly, $retries)
    {
        // Create a new PharData object for the destination tar file.
        $phar = new PharData($destination, 0, null, Phar::TAR | Phar::GZ);

        // Iterate over each chromosome and download its assembly mapping data.
        foreach ($chroms as $chrom) {
            $file = "$chrom.json";
            $mapEndpoint = "/map/human/{$sourceAssembly}/{$chrom}/{$targetAssembly}?";

            // Get the assembly mapping data from the Ensembl REST API.
            $response = null;
            $retry = 0;
            while ($response === null && $retry < $retries) {
                $response = $this->_ensembl_rest_client->perform_rest_action($mapEndpoint);
                $retry++;
            }

            if ($response !== null) {
                // Save the assembly mapping data to a temporary file.
                $tmpFile = tempnam(sys_get_temp_dir(), "tmp");
                file_put_contents($tmpFile, json_encode($response));

                // Add the temporary file to the tar file.
                $phar->addFile($tmpFile, $file);

                // Remove the temporary file.
                unlink($tmpFile);
            }
        }
    }

    /**
     * Get the GSA rsid map.
     *
     * @return array|null The GSA rsid map.
     */
    public function getGsaRsid(): array | null
    {
        if ($this->_gsa_rsid_map === null) {
            // download the file from the cloud, if not done already
            $url = "https://sano-public.s3.eu-west-2.amazonaws.com/gsa_rsid_map.txt.gz";
            $destination = "gsa_rsid_map.txt.gz";

            $rsidPath = $this->download_file($url, $destination);

            $csv = Reader::createFromPath($rsidPath, 'r');
            $csv->setDelimiter("\t");
            $csv->setHeaderOffset(0);

            $stmt = (new Statement())->offset(0)->limit(1); // Set header offset to 0
            $header = $stmt->process($csv)->getHeader();

            $stmt = new Statement();
            $rsids = [];

            foreach ($stmt->process($csv) as $row) {
                $rsids[] = array_combine($header, $row);
            }

            $this->_gsa_rsid_map = $rsids;
        }

        return $this->_gsa_rsid_map;
    }




    /**
     * Get the GSA chrpos map.
     *
     * @return array The GSA chrpos map.
     */
    public function getGsaChrpos(): array
    {
        // If the GSA chrpos map has not been loaded, download and process the file.
        if ($this->_gsa_chrpos_map === null) {
            // Download the GSA chrpos map file.
            $chrposPath = $this->download_file(
                "https://sano-public.s3.eu-west-2.amazonaws.com/gsa_chrpos_map.txt.gz",
                "gsa_chrpos_map.txt.gz"
            );

            // Uncompress the gzipped file.
            $csvContent = file_get_contents($chrposPath);

            if (substr($csvContent, 0, 2) === "\x1f\x8b") {
                $csvContent = gzdecode($csvContent);
            }

            // Load the uncompressed file into an array.
            $csv = Reader::createFromString($csvContent);
            $csv->setDelimiter("\t");
            $csv->setHeaderOffset(0);
            $chrposData = $csv->getRecords(["gsaname_chrpos", "gsachr", "gsapos", "gsacm"]);

            // Process and store the data in an array.
            $chrpos = [];
            foreach ($chrposData as $row) {
                $chrpos[] = [
                    "gsaname_chrpos" => $row["gsaname_chrpos"],
                    "gsachr" => $row["gsachr"],
                    "gsapos" => (int)$row["gsapos"],
                    "gsacm" => (float)$row["gsacm"],
                ];
            }

            $this->_gsa_chrpos_map = $chrpos;
        }

        // Return the GSA chrpos map.
        return $this->_gsa_chrpos_map;
    }

    /**
     * Get the path to the OpenSNP datadump file.
     *
     * @return string The path to the OpenSNP datadump file.
     */
    public function get_path_opensnp_datadump(): string
    {
        // Download the OpenSNP datadump file and return its path.
        return $this->download_file("https://opensnp.org/data/zip/opensnp_datadump.current.zip", "opensnp_datadump.current.zip");
    }

    /**
     * Downloads a file from a given URL and saves it to a specified location.
     *
     * @param string $url The URL of the file to download.
     * @param string $filename The name of the file to save the downloaded data to.
     * @param bool $compress Whether or not to compress the downloaded data.
     * @param int $timeout The maximum number of seconds to wait for the download to complete.
     *
     * @return string The full path to the downloaded file, or an empty string if the download failed.
     */
    public function download_file(string $url, string $filename, bool $compress = false, int $timeout = 30): string
    {
        // If compression is enabled and the filename doesn't end in .gz, add it.
        if ($compress && substr($filename, -3) !== '.gz') {
            $filename .= '.gz';
        }

        // Set the destination path for the downloaded file.
        $destination = $this->_resources_dir . DIRECTORY_SEPARATOR . $filename;

        // Create the directory for the file if it doesn't exist.
        if (!$this->create_dir(dirname($destination))) {
            return "";
        }

        // If the file doesn't already exist, download it.
        if (!file_exists($destination)) {
            try {
                // Set the timeout for the download.
                $httpClientOptions = [
                    'timeout' => $timeout,
                    'decode_content' => true,
                ];

                // Download the file using Guzzle HTTP client.
                $response = $this->httpClient->get($url, $httpClientOptions);

                // Get the response body.
                $data = $response->getBody()->getContents();

                // check if the file is gzipped
                if (substr($data, 0, 2) === "\x1f\x8b") {
                    $data = gzdecode($data);
                    $compress = true;
                }


                // If the download failed, throw an exception.
                if ($response->getStatusCode() !== 200) {
                    throw new Exception("Error downloading {$url}");
                }

                // Print a message indicating that the download was successful.
                $this->print_download_msg($destination);

                // Save the downloaded data to a file.
                $file = fopen($destination, 'wb');
                if ($compress) {
                    $this->write_data_to_gzip($file, $data);
                } else {
                    fwrite($file, $data);
                }
                fclose($file);
            } catch (Exception $err) {
                // If the download failed, print a warning message and try to download the file using HTTP instead of FTP.
                echo "Warning: {$err->getMessage()}\n";
                $destination = '';
                if (str_contains($url, 'ftp://')) {
                    $destination = $this->download_file(str_replace('ftp://', 'http://', $url), $filename, compress: $compress, timeout: $timeout);
                }
            }
        }


        // Return the path to the downloaded file.
        return $destination;
    }

    /**
     * Creates a directory if it doesn't already exist.
     *
     * @param string $path The path of the directory to create.
     *
     * @return bool True if the directory exists or was successfully created, false otherwise.
     */
    public function create_dir(string $path): bool
    {
        // Check if the directory already exists or if it was successfully created.
        if (!is_dir($path) && !mkdir($path, 0777, true) && !is_dir($path)) {
            return false;
        }

        return true;
    }

    /**
     * Prints a message indicating that a file is being downloaded.
     *
     * @param string $destination The path of the file being downloaded.
     *
     * @return void
     */
    private function print_download_msg(string $destination): void
    {
        echo "Downloading {$destination}\n";
    }

    /**
     * Writes data to a file in gzip format.
     *
     * @param resource $file The file resource to write to.
     * @param string $data The data to write to the file.
     *
     * @return void
     */
    private function write_data_to_gzip($file, string $data): void
    {
        fwrite($file, gzencode($data));
    }
}

