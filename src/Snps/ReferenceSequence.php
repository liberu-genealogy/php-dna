<?php

declare(strict_types=1);

namespace Dna\Snps;

/**
 * Class representing a reference sequence
 */
class ReferenceSequence
{
    private array $sequence = [];
    private string $md5 = '';
    private int $start = 0;
    private int $end = 0;
    private int $length = 0;

    public function __construct(
        private string $id,
        private string $url,
        private string $path,
        private string $assembly = '',
        private string $species = '',
        private string $taxonomy = ''
    ) {}

    /**
     * Get the sequence ID (uppercase alias)
     */
    public function getID(): string
    {
        return $this->id;
    }

    /**
     * Get the sequence ID
     */
    public function getId(): string
    {
        return $this->id;
    }

    /**
     * Get the chromosome (same as ID for reference sequences)
     */
    public function getChrom(): string
    {
        return $this->id;
    }

    /**
     * Get the build string (e.g., "B37" for GRCh37)
     */
    public function getBuild(): string
    {
        if (empty($this->assembly)) {
            return '';
        }
        return 'B' . substr($this->assembly, -2);
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
     * Load and return the sequence as an array of unsigned byte values.
     * Reads from gzip-compressed FASTA file.
     *
     * @return array Array of unsigned byte values (integers 0-255)
     */
    public function getSequence(): array
    {
        if (!empty($this->sequence)) {
            return $this->sequence;
        }
        $this->loadSequence();
        return $this->sequence;
    }

    /**
     * Get the MD5 hash of the sequence string.
     */
    public function getMd5(): string
    {
        if ($this->md5 === '' && !empty($this->path)) {
            $this->loadSequence();
        }
        return $this->md5;
    }

    /**
     * Get the start position (1-based).
     */
    public function getStart(): int
    {
        if ($this->start === 0 && !empty($this->path)) {
            $this->loadSequence();
        }
        return $this->start;
    }

    /**
     * Get the end position (1-based).
     */
    public function getEnd(): int
    {
        if ($this->end === 0 && !empty($this->path)) {
            $this->loadSequence();
        }
        return $this->end;
    }

    /**
     * Get the length of the sequence.
     */
    public function getLength(): int
    {
        if ($this->length === 0 && !empty($this->path)) {
            $this->loadSequence();
        }
        return $this->length;
    }

    /**
     * Clear cached sequence data.
     */
    public function clear(): void
    {
        $this->sequence = [];
        $this->md5 = '';
        $this->start = 0;
        $this->end = 0;
        $this->length = 0;
    }

    /**
     * Load sequence from the FASTA (possibly gzip-compressed) file.
     */
    private function loadSequence(): void
    {
        if (!file_exists($this->path)) {
            return;
        }

        // Read file content (handle gzip)
        if (str_ends_with($this->path, '.gz')) {
            $content = gzdecode(file_get_contents($this->path));
        } else {
            $content = file_get_contents($this->path);
        }

        if ($content === false) {
            return;
        }

        // Parse FASTA format: skip header lines (starting with '>'), concatenate sequence lines
        $lines = explode("\n", $content);
        $seqStr = '';
        foreach ($lines as $line) {
            $line = rtrim($line);
            if ($line === '' || str_starts_with($line, '>')) {
                continue;
            }
            $seqStr .= $line;
        }

        $this->sequence = array_values(unpack('C*', $seqStr) ?: []);
        $this->md5 = md5($seqStr);
        $len = strlen($seqStr);
        $this->start = $len > 0 ? 1 : 0;
        $this->end = $len;
        $this->length = $len;
    }

    /**
     * String representation
     */
    public function __toString(): string
    {
        return "ReferenceSequence(assembly={$this->assembly}, ID={$this->id})";
    }
}
