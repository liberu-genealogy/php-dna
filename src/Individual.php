<?php

require_once 'snps.php';
require_once 'snps/utils.php';

class Individual extends SNPs
{
    /**
     * Object used to represent and interact with an individual.
     *
     * The ``Individual`` object maintains information about an individual. The object provides
     * methods for loading an individual's genetic data (SNPs) and normalizing it for use with the
     * `lineage` framework.
     *
     * ``Individual`` inherits from ``snps.SNPs``.
     */
    
    private string $name;

    /**
     * Initialize an Individual object
     *
     * @param string $name Name of the individual
     * @param mixed $rawData Path(s) to file(s), bytes, or SNPs object(s) with raw genotype data
     * @param array $kwargs Parameters to snps.SNPs and/or snps.SNPs.merge
     */
    public function __construct(string $name, mixed $rawData = [], array $kwargs = [])
    {
        $this->name = $name;

        $snpsConstructorArgs = $this->getDefinedKwargs(new ReflectionMethod(SNPs::class, '__construct'), $kwargs);
        $snpsMergeArgs = $this->getDefinedKwargs(new ReflectionMethod(SNPs::class, 'merge'), $kwargs);

        parent::__construct(...array_values($snpsConstructorArgs));

        $rawDataArray = is_array($rawData) ? $rawData : [$rawData];

        foreach ($rawDataArray as $data) {
            $snps = $this->createSnpsObject($data, $snpsConstructorArgs);
            $this->merge([$snps], ...array_values($snpsMergeArgs));
        }
    }

    /**
     * Get defined keyword arguments for a method
     *
     * @param ReflectionMethod $method The method to get arguments for
     * @param array $kwargs The keyword arguments to filter
     * @return array The defined keyword arguments
     */
    private function getDefinedKwargs(ReflectionMethod $method, array $kwargs): array
    {
        $parameters = $method->getParameters();
        $definedKwargs = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $kwargs)) {
                $definedKwargs[$name] = $kwargs[$name];
            }
        }

        return $definedKwargs;
    }

    /**
     * Get the string representation of the Individual
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return sprintf("Individual('%s')", $this->name);
    }

    /**
     * Get the Individual's name
     *
     * @return string The name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get a variable-safe version of the Individual's name
     *
     * @return string The variable-safe name
     */
    public function getVarName(): string
    {
        return clean_str($this->name);
    }
}