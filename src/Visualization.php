<?php

require 'vendor/autoload.php';

use League\Csv\Reader;
use League\Csv\Writer;

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

function plot_chromosomes($one_chrom_match, $two_chrom_match, $cytobands, $path, $title, $build) {
    $image = imagecreatetruecolor(650, 900);
    $background_color = imagecolorallocate($image, 202, 202, 202);
    imagefill($image, 0, 0, $background_color);

    $df = _patch_chromosomal_features($cytobands, $one_chrom_match, $two_chrom_match);
    $collections = _chromosome_collections($df, $chrom_ybase, $chrom_height);

    foreach ($collections as $collection) {
        $color = imagecolorallocate($image, $collection['colors'][0] * 255, $collection['colors'][1] * 255, $collection['colors'][2] * 255);
        foreach ($collection['xranges'] as $xrange) {
            imagerectangle($image, $xrange['start'], $collection['yrange'][0], $xrange['start'] + $xrange['width'], $collection['yrange'][1], $color);
        }
    }

    if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) == 'png') {
        imagepng($image, $path);
    } else {
        imagejpeg($image, $path);
    }
    imagedestroy($image);
}
