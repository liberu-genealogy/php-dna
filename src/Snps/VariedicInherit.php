<?php

declare(strict_types=1);

namespace Php8\Migration;

use Exception;
use InvalidArgumentException;

class VariadicInherit
{
    public const ERR_MAGIC_SIGNATURE = 'WARNING: magic method signature for %s does not appear to match required signature';
    public const ERR_REMOVED = 'WARNING: the following function has been removed: %s.  Use this instead: %s';
    public const ERR_IS_RESOURCE = 'WARNING: this function no longer produces a resource: %s.  Usage of "is_resource($item)" should be replaced with "!empty($item)';
    public const ERR_MISSING_KEY = 'ERROR: missing configuration key %s';
    public const ERR_INVALID_KEY = 'ERROR: this configuration key is either missing or not callable: ';
    public const ERR_FILE_NOT_FOUND = 'ERROR: file not found: %s';
    public const WARN_BC_BREAKS = 'WARNING: the code in this file might not be compatible with PHP 8';
    public const NO_BC_BREAKS = 'SUCCESS: the code scanned in this file is potentially compatible with PHP 8';
    public const MAGIC_METHODS = 'The following magic methods were detected:';
    public const OK_PASSED = 'PASSED this scan: %s';
    public const TOTAL_BREAKS = 'Total potential BC breaks: %d' . PHP_EOL;
    public const KEY_REMOVED = 'removed';
    public const KEY_CALLBACK = 'callbacks';
    public const KEY_MAGIC = 'magic';
    public const KEY_RESOURCE = 'resource';

    public array $config = [];
    public string $contents = '';
    public array $messages = [];
    public array $magic = [];
    
    /**
     * VariadicInherit constructor.
     *
     * @param array $config Scan configuration
     * @throws InvalidArgumentException If a required configuration key is missing
     */
    public function __construct(array $config)
    {
        $this->config = $config;
        $required = [
            self::KEY_CALLBACK,
            self::KEY_REMOVED,
            self::KEY_MAGIC,
            self::KEY_RESOURCE,
        ];

        foreach ($required as $key) {
            if (!isset($this->config[$key])) {
                $message = sprintf(self::ERR_MISSING_KEY, $key);
                throw new InvalidArgumentException($message);
            }
        }
    }

    /**
     * Get the contents of a file.
     *
     * @param string $filePath Path to the file to scan
     * @return string The file contents with line breaks replaced by spaces
     * @throws InvalidArgumentException If the file is not found
     */
    public function getFileContents(string $filePath): string
    {
        if (!file_exists($filePath)) {
            $this->contents = '';
            throw new InvalidArgumentException(
                sprintf(self::ERR_FILE_NOT_FOUND, $filePath)
            );
        }

        $this->clearMessages();
        $this->contents = file_get_contents($filePath);
        $this->contents = str_replace(["\r", "\n"], ['', ' '], $this->contents);

        return $this->contents;
    }

    /**
     * Extracts the value immediately following the supplied word up until the supplied end
     *
     * @param string $contents : text to search (usually $this->contents)
     * @param string $key   : starting keyword or set of characters
     * @param string $delim : ending delimiter
     * @return string $name : classnames
     */
    /**
     * Get the value of a key from a string.
     *
     * @param string $contents The string to search
     * @param string $key The key to search for
     * @param string $delimiter The delimiter to use
     * @return string The value of the key, or an empty string if not found
     */
    public static function getKeyValue(
        string $contents,
        string $key,
        string $delimiter
    ): string {
        $position = strpos($contents, $key);

        if ($position === false) {
            return '';
        }

        $end = strpos($contents, $delimiter, $position + strlen($key) + 1);
        $value = substr(
            $contents,
            $position + strlen($key),
            $end - $position - strlen($key)
        );

        return is_string($value) ? trim($value) : '';
    }

    /**
     * Clears messages
     *
     * @return void
     */
    public function clearMessages() : void
    {
        $this->messages = [];
        $this->magic    = [];
    }

    /**
     * Returns messages
     *
     * @param bool $clear      : If TRUE, reset messages to []
     * @return array $messages : accumulated messages
     */
    public function getMessages(bool $clear = FALSE) : array
    {
        $messages = $this->messages;
        if ($clear) $this->clearMessages();
        return $messages;
    }

    /**
     * Returns 0 and adds OK message
     *
     * @param string $function
     * @return int 0
     */
    public function passedOK(string $function) : int
    {
        $this->messages[] = sprintf(self::OK_PASSED, $function);
        return 0;
    }

    /**
     * Runs all scans
     *
     * @return int $found : number of potential BC breaks found
     */
    public function runAllScans() : int
    {
        $found = 0;
        $found += $this->scanRemovedFunctions();
        $found += $this->scanIsResource();
        $found += $this->scanMagicSignatures();
        echo __METHOD__ . ':' . var_export($this->messages, TRUE) . "\n";
        $found += $this->scanFromCallbacks();
        return $found;
    }
    /**
     * Check for removed functions
     *
     * @return int $found : number of BC breaks detected
     */
    public function scanRemovedFunctions() : int
    {
        $found = 0;
        $config = $this->config[self::KEY_REMOVED] ?? NULL;
        // we add this extra safety check in case this method is called separately
        if (empty($config)) {
            $message = sprintf(self::ERR_MISSING_KEY, self::KEY_REMOVED);
            throw new Exception($message);
        }
        foreach ($config as $func => $replace) {
            $search1 = ' ' . $func . '(';
            $search2 = ' ' . $func . ' (';
            if (strpos($this->contents, $search1) !== FALSE
                || strpos($this->contents, $search2) !== FALSE) {
                $this->messages[] = sprintf(self::ERR_REMOVED, $func, $replace);
                $found++;
            }
        }
        return ($found === 0) ? $this->passedOK(__FUNCTION__) : $found;
    }
    /**
     * Check for is_resource usage
     * If "is_resource" found, check against list of functions
     * that no longer produce resources in PHP 8
     *
     * @return int $found : number of BC breaks detected
     */
    public function scanIsResource() : int
    {
        $found = 0;
        $search = 'is_resource';
        // if "is_resource" not found discontinue search
        if (strpos($this->contents, $search) === FALSE) return $this->passedOK(__FUNCTION__);
        // pull list of functions that now return objects instead of resources
        $config = $this->config[self::KEY_RESOURCE] ?? NULL;
        // we add this extra safety check in case this method is called separately
        if (empty($config)) {
            $message = sprintf(self::ERR_MISSING_KEY, self::KEY_RESOURCE);
            throw new Exception($message);
        }
        foreach ($config as $func) {
            if ((strpos($this->contents, $func) !== FALSE)) {
                $this->messages[] = sprintf(self::ERR_IS_RESOURCE, $func);
                $found++;
            }
        }
        return ($found === 0) ? $this->passedOK(__FUNCTION__) : $found;
    }
    /**
     * Scan for magic method signatures
     * NOTE: doesn't check inside parentheses.
     *       only checks for return data type + displays found and correct signatures for manual comparison
     *
     * @return int $found : number of invalid return data types
     */
    public function scanMagicSignatures() : int
    {
        // locate all magic methods
        $found   = 0;
        $matches = [];

        if (!empty($matches[1])) {
            $this->messages[] = self::MAGIC_METHODS;
            $config = $this->config[self::KEY_MAGIC] ?? NULL;
            // we add this extra safety check in case this method is called separately
            if (empty($config)) {
                $message = sprintf(self::ERR_MISSING_KEY, self::KEY_MAGIC);
                throw new Exception($message);
            }
            foreach ($matches[1] as $name) {
                $key = '__' . $name;
                // skip if key not found.  must not be a defined magic method
                if (!isset($config[$key])) continue;
                // record official signature
                $this->messages[] = 'Signature: ' . ($config[$key]['signature'] ?? 'Signature not found');
                $sub = $this->getKeyValue($this->contents, $key, '{');
                if ($sub) {
                    $sub = $key . $sub;
                    // record found signature
                    $this->messages[] = 'Actual   : ' . $sub;
                    // look for return type
                    if (strpos($sub, ':')) {
                        $ptn = '/.*?\(.*?\)\s*:\s*' . $config[$key]['return'] . '/';
                        // test for a match
                        if (!preg_match($ptn, $sub)) {
                            $this->messages[] = sprintf(self::ERR_MAGIC_SIGNATURE, $key);
                            $found++;
                        }
                    }
                }
            }
        }
        //echo __METHOD__ . ':' . var_export($this->messages, TRUE) . "\n";
        return ($found === 0) ? $this->passedOK(__FUNCTION__) : $found;
    }
    /**
     * Runs all scans key as defined in $this->config (bc_break_scanner.config.php)
     *
     * @return int $found : number of potential BC breaks found
     */
    public function scanFromCallbacks()
    {
        $found = 0;
        $list = array_keys($this->config[self::KEY_CALLBACK]);
        foreach ($list as $key) {
            $config = $this->config[self::KEY_CALLBACK][$key] ?? NULL;
            if (empty($config['callback']) || !is_callable($config['callback'])) {
                $message = sprintf(self::ERR_INVALID_KEY, self::KEY_CALLBACK . ' => ' . $key . ' => callback');
                throw new InvalidArgumentException($message);
            }
            if ($config['callback']($this->contents)) {
                $this->messages[] = $config['msg'];
                $found++;
            }
        }
        return $found;
    }

    /**
     * Get homozygous SNPs for a given chromosome.
     *
     * @param string $chromosome The chromosome to get homozygous SNPs for
     * @return mixed The result of the homozygous() method
     * @deprecated Use the homozygous() method instead
     */
    public function homozygous_snps(string $chromosome = '')
    {
        trigger_error(
            'This method has been renamed to `homozygous`.',
            E_USER_DEPRECATED
        );

        return $this->homozygous($chromosome);
    }

    /**
     * Check if the object is valid.
     *
     * @return bool The value of the "valid" property
     * @deprecated Use the "valid" property instead
     */
    public function is_valid(): bool
    {
        trigger_error(
            'This method has been renamed to `valid` and is now a property.',
            E_USER_DEPRECATED
        );

        return $this->valid;
    }

    /**
     * Predict ancestry using the ezancestry package.
     *
     * @param string|null $outputDirectory The output directory for predictions
     * @param bool $writePredictions Whether to write the predictions to files
     * @param string|null $modelsDirectory The directory containing the models
     * @param string|null $aisnpsDirectory The directory containing the AIsnps
     * @param int|null $nComponents The number of components for the model
     * @param int|null $k The number of nearest neighbors to use
     * @param string|null $thousandGenomesDirectory The directory containing the 1000 Genomes data
     * @param string|null $samplesDirectory The directory containing the samples
     * @param string|null $algorithm The algorithm to use for prediction
     * @param string|null $aisnpsSet The set of AIsnps to use
     * @return array The predicted ancestry values
     * @throws Exception If the ezancestry package is not installed
     */
    public function predict_ancestry(
        ?string $outputDirectory = null,
        bool $writePredictions = false,
        ?string $modelsDirectory = null,
        ?string $aisnpsDirectory = null,
        ?int $nComponents = null,
        ?int $k = null,
        ?string $thousandGenomesDirectory = null,
        ?string $samplesDirectory = null,
        ?string $algorithm = null,
        ?string $aisnpsSet = null
    ): array {
        return $this->getPredictions(
            $outputDirectory,
            $writePredictions,
            $modelsDirectory,
            $aisnpsDirectory,
            $nComponents,
            $k,
            $thousandGenomesDirectory,
            $samplesDirectory,
            $algorithm,
            $aisnpsSet
        );
    }

    /**
     * Get ancestry predictions using the ezancestry package.
     *
     * @param string|null $outputDirectory The output directory for predictions
     * @param bool $writePredictions Whether to write the predictions to files
     * @param string|null $modelsDirectory The directory containing the models
     * @param string|null $aisnpsDirectory The directory containing the AIsnps
     * @param int|null $nComponents The number of components for the model
     * @param int|null $k The number of nearest neighbors to use
     * @param string|null $thousandGenomesDirectory The directory containing the 1000 Genomes data
     * @param string|null $samplesDirectory The directory containing the samples
     * @param string|null $algorithm The algorithm to use for prediction
     * @param string|null $aisnpsSet The set of AIsnps to use
     * @return array The predicted ancestry values
     * @throws Exception If the ezancestry package is not installed or the object is not valid
     */
    public function getPredictions(
        ?string $outputDirectory = null,
        bool $writePredictions = false,
        ?string $modelsDirectory = null,
        ?string $aisnpsDirectory = null,
        ?int $nComponents = null,
        ?int $k = null,
        ?string $thousandGenomesDirectory = null,
        ?string $samplesDirectory = null,
        ?string $algorithm = null,
        ?string $aisnpsSet = null
    ): array {
        if (!$this->valid) {
            return [];
        }

        if (!class_exists('ezancestry\commands\Predict')) {
            throw new Exception(
                'Ancestry prediction requires the ezancestry package; please install it'
            );
        }

        $predict = new ezancestry\commands\Predict();

        $predictions = $predict->predict(
            $this->snps,
            $outputDirectory,
            $writePredictions,
            $modelsDirectory,
            $aisnpsDirectory,
            $nComponents,
            $k,
            $thousandGenomesDirectory,
            $samplesDirectory,
            $algorithm,
            $aisnpsSet
        );

        $maxPopValues = $this->maxPop($predictions[0]);
        $maxPopValues['ezancestry_df'] = $predictions;

        return $maxPopValues;
    }

    /**
     * Get the maximum population values from a prediction row.
     *
     * @param array $row The prediction row
     * @return array The maximum population values
     */
    private function maxPop(array $row): array
    {
        $populationCode = $row['predicted_population_population'];
        $populationDescription = $row['population_description'];
        $populationPercent = $row[$populationCode];
        $superpopulationCode = $row['predicted_population_superpopulation'];
        $superpopulationDescription = $row['superpopulation_name'];
        $superpopulationPercent = $row[$superpopulationCode];

        return [
            'population_code' => $populationCode,
            'population_description' => $populationDescription,
            '_percent' => $populationPercent,
            'superpopulation_code' => $superpopulationCode,
            'superpopulation_description' => $superpopulationDescription,
            'population_percent' => $superpopulationPercent,
        ];
    }
    
    /**
     * Compute cluster overlap based on a given threshold.
     *
     * @param float $clusterOverlapThreshold The threshold for cluster overlap
     * @return DataFrame The computed cluster overlap DataFrame
     */
    public function computeClusterOverlap(float $clusterOverlapThreshold = 0.95): DataFrame
    {
        $data = [
            'cluster_id' => ['c1', 'c3', 'c4', 'c5', 'v5'],
            'company_composition' => [
                '23andMe-v4',
                'AncestryDNA-v1, FTDNA, MyHeritage',
                '23andMe-v3',
                'AncestryDNA-v2',
                '23andMe-v5, LivingDNA',
            ],
            'chip_base_deduced' => [
                'HTS iSelect HD',
                'OmniExpress',
                'OmniExpress plus',
                'OmniExpress plus',
                'Illumina GSAs',
            ],
            'snps_in_cluster' => array_fill(0, 5, 0),
            'snps_in_common' => array_fill(0, 5, 0),
        ];

        $df = new DataFrame($data);
        $df->setIndex('cluster_id');

        $toRemap = null;

        if ($this->build !== 37) {
            $toRemap = clone $this;
            $toRemap->remap(37);
            $selfSnps = $toRemap->snps()->select(['chrom', 'pos'])->dropDuplicates();
        } else {
            $selfSnps = $this->snps()->select(['chrom', 'pos'])->dropDuplicates();
        }

        $chipClusters = $this->resources->getChipClusters();

        foreach ($df->indexValues() as $cluster) {
            $clusterSnps = $chipClusters->filter(
                function ($row) use ($cluster) {
                    return strpos($row['clusters'], $cluster) !== false;
                }
            )->select(['chrom', 'pos']);

            $df->loc[$cluster]['snps_in_cluster'] = count($clusterSnps);
            $df->loc[$cluster]['snps_in_common'] = count($selfSnps->merge($clusterSnps, 'inner'));

            $df['overlap_with_cluster'] = $df['snps_in_common'] / $df['snps_in_cluster'];
            $df['overlap_with_self'] = $df['snps_in_common'] / count($selfSnps);

            $maxOverlap = array_keys($df['overlap_with_cluster'], max($df['overlap_with_cluster']))[0];

            if (
                $df['overlap_with_cluster'][$maxOverlap] > $clusterOverlapThreshold
                && $df['overlap_with_self'][$maxOverlap] > $clusterOverlapThreshold
            ) {
                $this->cluster = $maxOverlap;
                $this->chip = $df['chip_base_deduced'][$maxOverlap];

                $companyComposition = $df['company_composition'][$maxOverlap];

                if (strpos($companyComposition, $this->source) !== false) {
                    if ($this->source === '23andMe' || $this->source === 'AncestryDNA') {
                        $i = strpos($companyComposition, 'v');
                        $this->chip_version = substr($companyComposition, $i, $i + 2);
                    }
                } else {
                    // Log a warning about the SNPs data source not found
                }
            }
        }

        return $df;
    }
}