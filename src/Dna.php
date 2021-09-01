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
 * Class Dna.
 */
class Dna
{
  /**
   * Stores name / path of output directory.
   *
   * @var string
   */
  protected $_output_dir;

  /**
   * Stores name / path of resources directory
   *
   * @var string
   */
  protected $_resources_dir;

  /**
   * Stores sources
   *
   * @var \Dna\Resources
   */
  protected $_resources;

  public function __construct(
    $output_dir = 'output',
    $resources_dir = 'resources123',
  )
    {
      $this->_output_dir = $output_dir;
      $this->_resources_dir = $resources_dir;
      $this->_resources = new \Dna\Resources($resources_dir = $resources_dir);
    }
}