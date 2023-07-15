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
        string $ID = "",
        string $url = "",
        string $path = "",
        string $assembly = "",
        string $species = "",
        string $taxonomy = ""
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
        $this->_sequence = array();
        $this->_md5 = "";
        $this->_start = 0;
        $this->_end = 0;
        $this->_length = 0;
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
        if ($this->sequence === null) {
            $this->loadSequence();
        }
        return $this->sequence;
    }

    /**
     * Returns the MD5 hash of the reference sequence.
     *
     * @return string The MD5 hash of the reference sequence.
     */
    public function getMd5(): string
    {
        if ($this->md5 === null) {
            $this->loadSequence();
        }
        return $this->md5;
    }

    /**
     * Returns the start position of the reference sequence.
     *
     * @return int The start position of the reference sequence.
     */
    public function getStart(): int
    {
        if ($this->start === null) {
            $this->loadSequence();
        }
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
            $data = gzdecode(file_get_contents($this->path));

            // Convert bytes to str and split lines
            $data = explode(PHP_EOL, utf8_encode($data));

            // Parse the first line
            [$this->start, $this->end] = $this->parseFirstLine($data[0]);

            // Convert str (FASTA sequence) to bytes
            $data = utf8_decode(implode("", array_slice($data, 1)));

            // Get MD5 of FASTA sequence
            $this->md5 = md5($data);

            // Store FASTA sequence as an array of integers
            $this->sequence = array_map('ord', str_split($data));
        }
    }

    private function parseFirstLine(string $firstLine): array
    {
        $items = explode(":", $firstLine);
        $index = array_search($this->ID, $items);
        return [
            intval($items[$index + 1]),
            intval($items[$index + 2])
        ];
    }
}
