<?php

namespace Dna\Snps\IO;

use Dna\Snps\SNPsResources;

/**
 * Class for reading and parsing raw data / genotype files.
 */
class Reader
{
    /**
     * Initialize a Reader.
     *
     * @param string $file
     *   Path to file to load or bytes to load.
     * @param bool $onlyDetectSource
     *   Flag to indicate if only detecting the source of the data.
     * @param SNPsResources|null $resources
     *   Instance of Resources.
     * @param array $rsids
     *   rsids to extract if loading a VCF file.
     */
    public function __construct(
        private string $file = '',
        private bool $onlyDetectSource = false,
        private ?SNPsResources $resources = null,
        private array $rsids = []
    ) {
    }

    /**
     * Read and parse a raw data / genotype file.
     *
     * @return array
     *   An array with the following items:
     *   - snps (pandas.DataFrame): Dataframe of parsed SNPs.
     *   - source (string): Detected source of SNPs.
     *   - phased (bool): Flag indicating if SNPs are phased.
     */
    public function read(): array
    {
        echo "Reading file: $this->file\n";
        $f = fopen($this->file, "r");
        echo static::readLine($f, false);
        return [];
    }

    /**
     * Read a line from the file.
     *
     * @param resource $f
     *   The file resource.
     * @param bool $decode
     *   Flag indicating if the line should be decoded as UTF-8.
     *
     * @return string
     *   The read line.
     */
    private static function readLine($f, bool $decode): string
    {
        if ($decode) {
            // https://stackoverflow.com/a/606199
            $data = fgets($f);
            return mb_convert_encoding($data, "UTF-8", mb_detect_encoding($data));;
        } else {
            return fgets($f);
        }
    }
}
