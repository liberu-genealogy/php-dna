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
   * Stores name / path of resources directory.
   *
   * @var string
   */
  protected $_resources_dir;

  public function __construct($resources_dir = 'resources')
    {
      print_r(realpath($_SERVER["DOCUMENT_ROOT"]));
    }
}