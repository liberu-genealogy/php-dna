<?php

namespace Dna;

use Dna\Snps\SNPs;
use Dna\Visualization;
use Dna\Triangulation;
use Exception;

/**
 * Matches SNP data between DNA kits
 */
class MatchKits
{
    /**
     * @var SNPs[] The DNA kit data to match
     */
    private array $kitsData = [];

    /**
     * @var array The matched SNP data
     */
    private array $matchedData = [];

    /**
     * @var Visualization
     */
    private Visualization $visualization;

    /**
     * @var Triangulation
     */
    private Triangulation $triangulation;

    /**
     * MatchKits constructor.
     *
     * @param Visualization $visualization
     * @param Triangulation $triangulation
     */
    public function __construct(Visualization $visualization, Triangulation $triangulation)
    {
        $this->visualization = $visualization;
        $this->triangulation = $triangulation;
    }

    /**
     * Match the loaded DNA kits
     *
     * @throws Exception If less than two kits are loaded
     */
    public function matchKits(): void
    {
        if (count($this->kitsData) < 2) {
            throw new Exception("At least two DNA kits are required for matching.");
        }

        $this->matchedData = []; // Reset matched data

        try {
            foreach ($this->kitsData[0]->getSnps() as $snp1) {
                foreach ($this->kitsData[1]->getSnps() as $snp2) {
                    if ($snp1['pos'] === $snp2['pos'] && $snp1['genotype'] === $snp2['genotype']) {
                        $this->matchedData[] = $snp1;
                    }
                }
            }
        } catch (Exception $e) {
            throw new Exception("Error matching DNA kits: " . $e->getMessage());
        }
    }

    /**
     * @return array The matched SNP data
     */
    public function getMatchedData(): array
    {
        return $this->matchedData;
    }

    /**
     * Load DNA kit data
     *
     * @param SNPs[] $kitsData The kit data to load
     * @throws Exception If the input is not an array of SNPs objects
     */
    public function setKitsData(array $kitsData): void
    {
        foreach ($kitsData as $kit) {
            if (!$kit instanceof SNPs) {
                throw new Exception("Invalid input: All elements must be instances of SNPs class.");
            }
        }
        $this->kitsData = $kitsData;
    }

    /**
     * Triangulate kits
     *
     * @throws Exception If less than three kits are loaded
     */
    public function triangulateKits(): void
    {
        if (count($this->kitsData) < 3) {
            throw new Exception("At least three DNA kits are required for triangulation.");
        }

        try {
            $this->matchedData = $this->triangulation->compareMultipleKits($this->kitsData);
        } catch (Exception $e) {
            throw new Exception("Error triangulating DNA kits: " . $e->getMessage());
        }
    }

    

    /**
     * Visualize matched data
     *
     * @param string $filename The filename for the generated plot
     * @param string $title The title for the plot
     * @param string $build The genome build version
     * @param string $format The image format for the plot
     * @throws Exception If visualization fails
     */
    public function visualizeMatchedData(string $filename, string $title, string $build, string $format): void
    {
        if (empty($this->matchedData)) {
            throw new Exception("No matched data to visualize. Run matchKits() or triangulateKits() first.");
        }

        try {
            $this->visualization->plotChromosomes($this->matchedData, $filename, $title, $build, $format);
        } catch (Exception $e) {
            throw new Exception("Error visualizing matched data: " . $e->getMessage());
        }
    }
}

if (php_sapi_name() == "cli") {
    try {
        $visualization = new Visualization();
        $triangulation = new Triangulation();
        $matchKits = new MatchKits($visualization, $triangulation);

        echo "Enter the number of kits to compare (2 for matching, 3 or more for triangulation): ";
        $numKits = intval(trim(fgets(STDIN)));

        if ($numKits < 2) {
            throw new Exception("At least two kits are required for comparison.");
        }

        $kitPaths = [];
        for ($i = 0; $i < $numKits; $i++) {
            echo "Enter file path for Kit " . ($i + 1) . ": ";
            $path = trim(fgets(STDIN));
            if (!file_exists($path)) {
                throw new Exception("File not found: $path");
            }
            $kitPaths[] = $path;
        }

        $kitsData = array_map(function($path) {
            return new SNPs($path);
        }, $kitPaths);

        $matchKits->setKitsData($kitsData);

        if ($numKits == 2) {
            $matchKits->matchKits();
            echo "Matching kits...\n";
        } else {
            $matchKits->triangulateKits();
            echo "Triangulating kits...\n";
        }

        echo "Enter filename for the visualization (e.g., matched_data.png): ";
        $filename = trim(fgets(STDIN));

        echo "Enter title for the plot: ";
        $title = trim(fgets(STDIN));

        echo "Enter genome build version (e.g., GRCh37): ";
        $build = trim(fgets(STDIN));

        echo "Enter image format (png, jpeg, or jpg): ";
        $format = strtolower(trim(fgets(STDIN)));

        if (!in_array($format, ['png', 'jpeg', 'jpg'])) {
            throw new Exception("Invalid image format. Please use png, jpeg, or jpg.");
        }

        $matchKits->visualizeMatchedData($filename, $title, $build, $format);

        echo "Matched data visualization has been generated: $filename\n";
    } catch (Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
        exit(1);
    }
}
?>

