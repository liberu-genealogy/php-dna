<?php

/**
 * php-dna.
 *
 * Tools for genetic genealogy and the analysis of consumer DNA test results.
 *
 * @author    Liberu Software Ltd <support@laravel-liberu.com>
 * @copyright Copyright (c) 2020-2023, Liberu Software Ltd
 * @license   MIT
 *
 * @link      http://github.com/laravel-liberu/php-dna
 */

namespace Dna;

/**
 * Class Dna.
 */
class Dna
{
    /**
     * The directory where output files will be written.
     *
     * @var string
     */
    protected string $_outputDir;

    /**
     * The directory containing resource files used for DNA analysis.
     *
     * @var string
     */
    protected string $_resourcesDir;

    /**
     * Provides access to DNA resource files.
     *
     * @var \Dna\Resources
     */
    protected Resources $_resources;

    public function __construct(
        string $outputDirectory = 'output',
        string $resourcesDirectory = 'resources'
    ) {
        $this->_outputDir = $outputDirectory;
        $this->_resourcesDir = $resourcesDirectory;
        $this->_resources = Resources::getInstance();
    }
}