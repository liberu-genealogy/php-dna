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

    /**
     * Constructor for the ResourceManager class
     * @param string $resources_dir The directory where the resources are located
     */

    public function __construct(string $resources_dir = "resources") {
        $this->_resources_dir = realpath($resources_dir);
        $this->_ensembl_rest_client = new EnsemblRestClient();
        $this->_init_resource_attributes();
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
    private function _init_resource_attributes(): void {
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
    private function referenceChromsAvailable(string $assembly, array $chroms): bool {
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
    private function createReferenceSequences(string $fastaPath, string $faiPath, string $dictPath): array {
        // TODO: Implement reference sequence creation
        return [];
    }

    /**
     * Retrieves paths to reference sequences for the specified assembly and chromosomes
     * @param string $assembly The assembly to retrieve reference sequence paths for
     * @param array $chroms The chromosomes to retrieve reference sequence paths for
     * @return array An array of paths to reference sequences
     */
    private function getPathsReferenceSequences(string $assembly, array $chroms): array {
        // TODO: Implement reference sequence path retrieval
        return [];
    }
    
    /**
     * Get assembly mapping data.
     *
     * @param string $sourceAssembly {'NCBI36', 'GRCh37', 'GRCh38'} assembly to remap from
     * @param string $targetAssembly {'NCBI36', 'GRCh37', 'GRCh38'} assembly to remap to
     *
     * @return array array of json assembly mapping data if loading was successful, else []
     */
    public function getAssemblyMappingData(string $sourceAssembly, string $targetAssembly): array {
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
      $paths[] = $this->_download_file("https://opensnp.org/data/662.23andme.340", "662.23andme.340.txt.gz", true);

      // Download FTDNA Illumina example dataset.
      $paths[] = $this->_download_file("https://opensnp.org/data/662.ftdna-illumina.341", "662.ftdna-illumina.341.csv.gz", true);

      return $paths;
  }  

  
}