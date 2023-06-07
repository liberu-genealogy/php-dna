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
      $resources["chip_clusters"] = $this->getChipClusters();

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

      return $this->reference_sequences;
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
      $dbsnp_151_37_reverse = $this->getDbsnp15137Reverse();

      // Return an array of resources.
      return [
          "rsid_map" => $rsid_map,
          "chrpos_map" => $chrpos_map,
          "dbsnp_151_37_reverse" => $dbsnp_151_37_reverse,
      ];
  }
  
  /**
   * Get the chip clusters data.
   *
   * @return DataFrame The chip clusters data.
   */
  public function get_chip_clusters() {
    // If the chip clusters data has not been loaded yet, download and process it.
    if ($this->_chip_clusters === null) {
        // Download the chip clusters file.
        $chip_clusters_path = $this->_download_file(
            "https://supfam.mrc-lmb.cam.ac.uk/GenomePrep/datadir/the_list.tsv.gz",
            "chip_clusters.tsv.gz"
        );

        // Load the chip clusters file into a DataFrame.
        $df = \DataFrame::from_csv($chip_clusters_path, [
            'sep' => "\t",
            'names' => ["locus", "clusters"],
            'dtypes' => [
                "locus" => 'string', "clusters" => \CategoricalDtype::create(['ordered' => false])
            ]
        ]);

        // Split the locus column into separate columns for chromosome and position.
        $clusters = $df['clusters'];
        $locus = $df['locus']->str_split(":", ['expand' => true]);
        $locus->rename(['chrom', 'pos'], ['axis' => 1, 'inplace' => true]);

        // Convert the position column to an unsigned 32-bit integer.
        $locus['pos'] = $locus['pos']->astype('uint32');

        // Convert the chromosome column to a categorical data type.
        $locus['chrom'] = $locus['chrom']->astype(\CategoricalDtype::create(['ordered' => false]));

        // Add the clusters column to the locus DataFrame.
        $locus['clusters'] = $clusters;

        // Save the processed chip clusters data to the object.
        $this->_chip_clusters = $locus;
    }

    // Return the chip clusters data.
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
          $lowQualitySnpsPath = $this->downloadFile(
              "https://supfam.mrc-lmb.cam.ac.uk/GenomePrep/datadir/badalleles.tsv.gz",
              "low_quality_snps.tsv.gz"
          );

          // Load the low quality SNPs file into an array.
          $fileContents = file_get_contents($lowQualitySnpsPath);
          $rows = explode("\n", $fileContents);

          // Process the low quality SNPs data into an array of arrays.
          $clusterDfs = [];

          foreach ($rows as $row) {
              if (empty($row)) {
                  continue;
              }

              [$cluster, $loci] = explode("\t", $row);
              $lociSplit = explode(",", $loci);

              foreach ($lociSplit as $locus) {
                  $clusterDfs[] = ['cluster' => $cluster, 'locus' => $locus];
              }
          }

          // Transform the low quality SNPs data into an array of arrays with separate columns for chromosome and position.
          $transformedData = [];

          foreach ($clusterDfs as $clusterDf) {
              [$chrom, $pos] = explode(':', $clusterDf['locus']);
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
          $lowQualitySnpsPath = $this->downloadFile(
              "https://supfam.mrc-lmb.cam.ac.uk/GenomePrep/datadir/badalleles.tsv.gz",
              "low_quality_snps.tsv.gz"
          );

          // Load the low quality SNPs file into an array.
          $fileContents = file_get_contents($lowQualitySnpsPath);
          $rows = explode("\n", $fileContents);

          // Process the low quality SNPs data into an array of arrays.
          $clusterDfs = [];

          foreach ($rows as $row) {
              if (empty($row)) {
                  continue;
              }

              [$cluster, $loci] = explode("\t", $row);
              $lociSplit = explode(",", $loci);

              foreach ($lociSplit as $locus) {
                  $clusterDfs[] = ['cluster' => $cluster, 'locus' => $locus];
              }
          }

          // Transform the low quality SNPs data into an array of arrays with separate columns for chromosome and position.
          $transformedData = [];

          foreach ($clusterDfs as $clusterDf) {
              [$chrom, $pos] = explode(':', $clusterDf['locus']);
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
}