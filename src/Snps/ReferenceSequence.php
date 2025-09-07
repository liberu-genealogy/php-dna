<?php

declare(strict_types=1);

namespace Dna\Snps;

/**
 * Class representing a reference sequence
 */
class ReferenceSequence
{
    public function __construct(
        private string $id,
        private string $url,
        private string $path,
        private string $assembly,
        private string $species,
        private string $taxonomy
    ) {}

    /**
     * Get the sequence ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the URL
     */
    public function getUrl(): string
    {
        return $this->url;
    }

    /**
     * Get the file path
     */
    public function getPath(): string
    {
        return $this->path;
    }

    /**
     * Get the assembly
     */
    public function getAssembly(): string
    {
        return $this->assembly;
    }

    /**
     * Get the species
     */
    public function getSpecies(): string
    {
        return $this->species;
    }

    /**
     * Get the taxonomy
     */
    public function getTaxonomy(): string
    {
        return $this->taxonomy;
    }

    /**
     * Check if the reference sequence file exists
     */
    public function exists(): bool
    {
        return file_exists($this->path);
    }

    /**
     * Get the size of the reference sequence file
     */
    public function getSize(): int
    {
        return $this->exists() ? filesize($this->path) : 0;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return "ReferenceSequence(id='{$this->id}', assembly='{$this->assembly}', species='{$this->species}')";
    }
}