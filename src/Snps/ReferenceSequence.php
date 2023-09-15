<?php

namespace Dna\Snps;

/**
 * Object used to represent and interact with a reference sequence.
 */
class ReferenceSequence
{

    private string $_ID; // The ID of the reference sequence chromosome.
    private string $_url; // The URL to the Ensembl reference sequence.
    private string $_path; // The path to the local reference sequence.
    private string $_assembly; // The reference sequence assembly (e.g., "GRCh37").
    private string $_species; // The reference sequence species.
    private string $_taxonomy; // The reference sequence taxonomy.
    private array $sequence; // Array to store the sequence
    private string $md5; // MD5 hash of the sequence
    private int $start; // Start position of the sequence
    private int $end; // End position of the sequence
    private int $length; // Length of the sequence

    public function __construct(
        private string $ID = "",
        private string $url = "",
        private string $path = "",
        private string $assembly = "",
        private string $species = "",
        private string $taxonomy = ""
    ) {
        /* Initialize a ReferenceSequence object.
        
        Parameters:
          ID: reference sequence chromosome
          url: url to Ensembl reference sequence
          path: path to local reference sequence
          assembly: reference sequence assembly (e.g., "GRCh37")
          species: reference sequence species
          taxonomy: reference sequence taxonomy
        
        References:
          1. The Variant Call Format (VCF) Version 4.2 Specification, 8 Mar 2019,
            https://samtools.github.io/hts-specs/VCFv4.2.pdf
        */
        $this->_ID = $ID;
        $this->_url = $url;
        $this->_path = $path;
        $this->_assembly = $assembly;
        $this->_species = $species;
        $this->_taxonomy = $taxonomy;
        $this->sequence = array();
        $this->md5 = "";
        $this->start = 0;
        $this->end = 0;
        $this->length = 0;
        $this->clear(); // Initialize the object with default values
        $this->loadSequence();
    }

    public function __toString()
    {
        return "ReferenceSequence(assembly={$this->_assembly}, ID={$this->_ID})";
    }

    /**
     * Returns the ID of the reference sequence chromosome.
     *
     * @return string The ID of the reference sequence chromosome.
     */
    public function getID(): string
    {
        return $this->_ID;
    }

    /**
     * Returns the ID of the reference sequence chromosome.
     *
     * @return string The ID of the reference sequence chromosome.
     */
    public function getChrom(): string
    {
        return $this->_ID;
    }

    /**
     * Returns the URL to the Ensembl reference sequence.
     *
     * @return string The URL to the Ensembl reference sequence.
     */
    public function getUrl(): string
    {
        return $this->_url;
    }

    /**
     * Returns the path to the local reference sequence.
     *
     * @return string The path to the local reference sequence.
     */
    public function getPath(): string
    {
        return $this->_path;
    }

    /**
     * Returns the reference sequence assembly (e.g., "GRCh37").
     *
     * @return string The reference sequence assembly.
     */
    public function getAssembly(): string
    {
        return $this->_assembly;
    }

    /**
     * Returns the build number of the reference sequence assembly.
     *
     * @return string The build number of the reference sequence assembly.
     */
    public function getBuild(): string
    {
        return "B" . substr($this->_assembly, -2);
    }
    /**
     * Returns the species of the reference sequence.
     *
     * @return string The species of the reference sequence.
     */
    public function getSpecies(): string
    {
        return $this->_species;
    }

    /**
     * Returns the taxonomy of the reference sequence.
     *
     * @return string The taxonomy of the reference sequence.
     */
    public function getTaxonomy(): string
    {
        return $this->_taxonomy;
    }

    /**
     * Returns the reference sequence.
     *
     * @return array The reference sequence.
     */
    public function getSequence(): array
    {
        $this->loadSequence();
        return $this->sequence;
    }

    /**
     * Returns the MD5 hash of the reference sequence.
     *
     * @return string The MD5 hash of the reference sequence.
     */
    public function getMd5(): string
    {
        $this->loadSequence();
        return $this->md5;
    }

    /**
     * Returns the start position of the reference sequence.
     *
     * @return int The start position of the reference sequence.
     */
    public function getStart(): int
    {
        $this->loadSequence();        
        return $this->start;
    }

    public function getEnd(): int
    {
        $this->loadSequence(); // Load the sequence
        return $this->end; // Return the end position
    }

    public function getLength(): int
    {
        $this->loadSequence(); // Load the sequence
        return count($this->sequence); // Return the length of the sequence
    }

    public function clear(): void
    {
        $this->sequence = []; // Clear the sequence array
        $this->md5 = ""; // Clear the MD5 hash
        $this->start = 0; // Reset the start position
        $this->end = 0; // Reset the end position
        $this->length = 0; // Reset the length
    }

    private function loadSequence(): void
    {
        if (!count($this->sequence)) {
            // Decompress and read file
            $data = file_get_contents($this->_path);

            // check if file is gzipped
            if (substr($data, 0, 2) === "\x1f\x8b") {
                // Decompress the file
                $data = gzdecode($data);
            }

            // Detect character encoding
            $encoding = mb_detect_encoding($data);

            // Convert to UTF-8 if not already
            if (!empty($encoding) && $encoding !== 'UTF-8') {
                $data = mb_convert_encoding($data, 'UTF-8', $encoding);
            }

            // Split lines
            $lines = explode(PHP_EOL, $data);

            // Parse the first line
            [$this->start, $this->end] = array_pad(
                $this->parseFirstLine($lines[0]),
                2,
                ""
            );

            // Convert str (FASTA sequence) to bytes
            $fastaSequence = implode("", array_slice($lines, 1));

            // Get MD5 of FASTA sequence
            $this->md5 = md5($fastaSequence);

            // Store FASTA sequence as an array of integers
            $this->sequence = array_map('ord', mb_str_split($fastaSequence, 1, 'UTF-8'));
        }
    }


    private function parseFirstLine(string $firstLine): array
    {
        $items = explode(":", $firstLine);
        $index = array_search($this->_ID, $items);
        $item1 = $items[$index + 1] ?? "";
        $item2 = $items[$index + 2] ?? "";
        return [
            intval($item1),
            intval($item2)
        ];
    }
}
