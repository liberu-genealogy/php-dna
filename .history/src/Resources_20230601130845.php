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

namespace Dna;

/**
 * Class Resources.
 */
class Resources extends \Dna\Snps\SNPsResources
{

  protected $_genetic_map = '{}';
  protected $_genetic_map_name = '';
  protected $_cytoBand_hg19 = [];
  protected $_knownGene_hg19 = [];
  protected $_kgXref_hg19 = [];

  public function __construct($resources_dir = 'resources')
    {
      parent::__construct($resources_dir = $resources_dir);
    }
}