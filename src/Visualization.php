<?php

namespace Dna;

use League\Csv\Reader;
use League\Csv\Writer;
use Dna\Helpers\CSVGenerator;
use Exception;

/**
 * Generates visualizations of DNA data
 */
class Visualization
{
    /**
     * Plot the provided SNP data on a chromosome map
     *
     * @param array  $data     The SNP data to plot
     * @param string $filename The filename for the generated plot
     * @param string $title    The title for the plot
     * @param string $build    The genome build version
     * @param string $format   The image format for the plot
     * @throws Exception If there's an error during visualization
     */
    public function plotChromosomes(
        array $data,
        string $filename,
        string $title,
        string $build,
        string $format
    ): void {
        try {
            $this->validateInput($data, $filename, $format);
            $chromosomeCollections = $this->chromosomeCollections($data);
            $chromosomalFeatures = $this->patchChromosomalFeatures($data);

            // Visualization code...
            // (Implement the actual visualization logic here)

            $this->generateCSV($data, $filename);
        } catch (Exception $e) {
            throw new Exception("Error generating visualization: " . $e->getMessage());
        }
    }

    /**
     * Validate input data for visualization
     *
     * @param array  $data     The SNP data to plot
     * @param string $filename The filename for the generated plot
     * @param string $format   The image format for the plot
     * @throws Exception If input is invalid
     */
    private function validateInput(array $data, string $filename, string $format): void
    {
        if (empty($data)) {
            throw new Exception("No data provided for visualization.");
        }
        if (empty($filename)) {
            throw new Exception("Filename is required for visualization output.");
        }
        if (!in_array(strtolower($format), ['png', 'jpeg', 'jpg'])) {
            throw new Exception("Invalid image format. Please use png, jpeg, or jpg.");
        }
    }

    /**
     * Generate chromosome collections for visualization
     *
     * @param array $data The SNP data
     * @return array Chromosome collections
     */
    private function chromosomeCollections(array $data): array
    {
        $collections = [];
        $yPositions = $this->calculateYPositions($data);
        $height = 1; // Adjust as needed

        foreach ($data as $chrom => $group) {
            $yrange = [$yPositions[$chrom], $height];
            $xranges = [];
            foreach ($group as $snp) {
                $xranges[] = ['start' => $snp['pos'], 'width' => 1]; // Assuming SNP position is a single point
            }
            $collections[] = ['xranges' => $xranges, 'yrange' => $yrange, 'colors' => $this->generateColorScheme(count($group))];
        }
        return $collections;
    }

    /**
     * Calculate Y positions for chromosomes
     *
     * @param array $data The SNP data
     * @return array Y positions for each chromosome
     */
    private function calculateYPositions(array $data): array
    {
        $yPositions = [];
        $currentY = 0;
        foreach (array_keys($data) as $chrom) {
            $yPositions[$chrom] = $currentY;
            $currentY += 2; // Adjust spacing as needed
        }
        return $yPositions;
    }

    /**
     * Patch chromosomal features for visualization
     *
     * @param array $data The SNP data
     * @return array Patched chromosomal features
     */
    private function patchChromosomalFeatures(array $data): array
    {
        $features = [];
        foreach ($data as $chromosome => $snps) {
            $chromosomeLength = max(array_column($snps, 'pos'));
            $features[$chromosome][] = ['start' => 0, 'end' => $chromosomeLength, 'gie_stain' => 'gneg'];
            foreach ($snps as $snp) {
                $features[$chromosome][] = [
                    'start' => $snp['pos'],
                    'end' => $snp['pos'] + 1,
                    'gie_stain' => 'snp'
                ];
            }
        }
        return $features;
    }

    /**
     * Generate CSV file from matched data
     *
     * @param array  $matchedData The matched SNP data
     * @param string $filename    The filename for the generated plot
     */
    private function generateCSV(array $matchedData, string $filename): void
    {
        $csvPath = str_replace(['.png', '.jpeg', '.jpg'], '.csv', $filename);
        CSVGenerator::generate($matchedData, $csvPath);
    }

    /**
     * Generate color scheme for visualization
     *
     * @param int $numColors Number of colors to generate
     * @return array Array of color strings
     */
    private function generateColorScheme(int $numColors): array
    {
        $colors = [];
        for ($i = 0; $i < $numColors; $i++) {
            $hue = $i * (360 / $numColors);
            $colors[] = "hsl(" . $hue . ", 100%, 50%)";
        }
        return $colors;
    }
}
