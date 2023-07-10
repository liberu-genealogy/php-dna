<?php

/**
 * php-dna.
 *
 * tools for genetic genealogy and the analysis of consumer DNA test results
 *
 * @author          Laravel Software Ltd <support@laravel-liberu.com>
 * @copyright       Copyright (c) 2020-2023, Laravel Software Ltd
 * @license         MIT
 *
 * @link            http://github.com/laravel-liberu/php-dna
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
    $resources_dir = 'resources',
  )
    {
      $this->_output_dir = $output_dir;
      $this->_resources_dir = $resources_dir;
      $this->_resources = Resources::getInstance();
    }
}