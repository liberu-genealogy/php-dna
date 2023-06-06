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


}