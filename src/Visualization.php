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

function plot_chromosomes($matchedData, $path, $title, $build, $format) {
    if ($format == 'csv') {
        generate_csv($matchedData, $path);
        return;
    }
    $one_chrom_match = $matchedData;
    $two_chrom_match = []; // Assuming no data for two chromosome matches in this context
    $cytobands = []; // Assuming cytobands data needs to be integrated or is not required for matched SNP visualization
    $image = imagecreatetruecolor(650, 900);
    $background_color = imagecolorallocate($image, 202, 202, 202);
    imagefill($image, 0, 0, $background_color);

    $df = _patch_chromosomal_features($cytobands, $one_chrom_match, $two_chrom_match);
    $collections = _chromosome_collections($df, $chrom_ybase, $chrom_height);

    foreach ($collections as $collection) {
    if ($format == 'svg') {
        $svgFile = fopen($path, 'w');
        fwrite($svgFile, "<svg width='1300' height='1800' xmlns='http://www.w3.org/2000/svg'>\n");
        foreach ($collections as $collection) {
            // Enhanced color scheme
            $colorIndex = array_search($collection, $collections);
            $color = $colors[$colorIndex];
            foreach ($collection['xranges'] as $xrange) {
                fwrite($svgFile, "<rect x='{$xrange['start']}' y='{$collection['yrange'][0]}' width='{$xrange['width']}' height='" . ($collection['yrange'][1] - $collection['yrange'][0]) . "' fill='{$color}' />\n");
            }
        }
        // Adding labels to the SVG
        foreach ($collections as $index => $collection) {
            $labelX = $collection['xranges'][0]['start'];
            $labelY = $collection['yrange'][0] - 10; // Adjust label position above the rectangle
            $label = "Chromosome " . ($index + 1);
            fwrite($svgFile, "<text x='{$labelX}' y='{$labelY}' font-family='Arial' font-size='14' fill='black'>{$label}</text>\n");
        }
        fwrite($svgFile, "</svg>");
        fclose($svgFile);
        return;
    }
    $colors = generate_color_scheme(count($collections));
        CSVGenerator::generate($matchedData, str_replace('.svg', '.csv', $path));
        $color = imagecolorallocate($image, $collection['colors'][0] * 255, $collection['colors'][1] * 255, $collection['colors'][2] * 255);
        foreach ($collection['xranges'] as $xrange) {
            imagerectangle($image, $xrange['start'], $collection['yrange'][0], $xrange['start'] + $xrange['width'], $collection['yrange'][1], $color);
        }
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
