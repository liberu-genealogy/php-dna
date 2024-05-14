<?php

use League\Csv\Reader;
use League\Csv\Writer;
use src\Helpers\CSVGenerator;

function _chromosome_collections($df, $y_positions, $height) {
    $collections = [];
    foreach ($df as $chrom => $group) {
        $yrange = [$y_positions[$chrom], $height];
        $xranges = [];
        foreach ($group as $data) {
            $xranges[] = ['start' => $data['start'], 'width' => $data['end'] - $data['start']];
        }
        $collections[] = ['xranges' => $xranges, 'yrange' => $yrange, 'colors' => array_column($group, 'colors')];
    }
    return $collections;
}

function _patch_chromosomal_features($cytobands, $one_chrom_match, $two_chrom_match) {
    $df = [];
    foreach ($cytobands as $chromosome => $data) {
        $chromosome_length = max(array_column($data, 'end'));
        $df[$chromosome][] = ['start' => 0, 'end' => $chromosome_length, 'gie_stain' => 'gneg'];
        foreach ($one_chrom_match as $match) {
            if ($match['chrom'] == $chromosome) {
                $df[$chromosome][] = ['start' => $match['start'], 'end' => $match['end'], 'gie_stain' => 'one_chrom'];
            }
        }
        foreach ($two_chrom_match as $match) {
            if ($match['chrom'] == $chromosome) {
                $df[$chromosome][] = ['start' => $match['start'], 'end' => $match['end'], 'gie_stain' => 'two_chrom'];
            }
        }
    }
    return $df;
}

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
     */
    public function plotChromosomes(
        array $data,
        string $filename,
        string $title,
        string $build,
        string $format
    ): void {
        // Visualization code...
    }
}
function generate_csv($matchedData, $path) {
    $csvFile = fopen($path, 'w');
    foreach ($matchedData as $data) {
        fputcsv($csvFile, $data);
    }
    fclose($csvFile);
}
        CSVGenerator::generate($matchedData, str_replace(['.png', '.jpeg', '.jpg'], '.csv', $path));
function generate_color_scheme($numColors) {
    $colors = [];
    for ($i = 0; $i < $numColors; $i++) {
        $hue = $i * (360 / $numColors);
        $colors[] = "hsl(" . $hue . ", 100%, 50%)";
    }
    return $colors;
}
