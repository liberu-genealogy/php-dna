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
    
    private string $_name;

    public function __construct(string $name, mixed $raw_data = [], array $kwargs = [])
    {
        /**
         * Initialize an ``Individual`` object.
         *
         * Parameters
         * ----------
         * name : str
         *     name of the individual
         * raw_data : str, bytes, ``SNPs`` (or list or tuple thereof)
         *     path(s) to file(s), bytes, or ``SNPs`` object(s) with raw genotype data
         * kwargs : array
         *     parameters to ``snps.SNPs`` and/or ``snps.SNPs.merge``
         */
        $this->_name = $name;

        $init_args = $this->_get_defined_kwargs(new ReflectionMethod(SNPs::class, '__construct'), $kwargs);
        $merge_args = $this->_get_defined_kwargs(new ReflectionMethod(SNPs::class, ''), $kwargs);

        parent::__construct(...array_values($init_args));

        if (!is_array($raw_data)) {
            $raw_data = [$raw_data];
        }

        foreach ($raw_data as $file) {
            $s = $file instanceof SNPs ? $file : new SNPs($file, ...array_values($init_args));
            $this->merge([$s], ...array_values($merge_args));
        }
    }

    private function _get_defined_kwargs(ReflectionMethod $callable, array $kwargs): array
    {
        $parameters = $callable->getParameters();
        $defined_kwargs = [];
        foreach ($parameters as $parameter) {
            $name = $parameter->getName();
            if (array_key_exists($name, $kwargs)) {
                $defined_kwargs[$name] = $kwargs[$name];
            }
        }

        return $defined_kwargs;
    }

    public function __toString(): string
    {
        return sprintf("Individual('%s')", $this->_name);
    }

    public function getName(): string
    {
        /**
         * Get this ``Individual``'s name.
         *
         * Returns
         * -------
         * str
         */
        return $this->_name;
    }

    public function getVarName(): string
    {
        return clean_str($this->_name);
    }
}