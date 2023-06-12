<?php

function plot_chromosomes($one_chrom_match, $two_chrom_match, $cytobands, $path, $title, $build) {
    // Height of each chromosome
    $chrom_height = 1.25;

    // Spacing between consecutive chromosomes
    $chrom_spacing = 1;

    // Decide which chromosomes to use
    $chromosome_list = array_merge(range(1, 22), ['Y', 'X']);
    $chromosome_list = array_map(function($chrom) {
        return 'chr' . $chrom;
    }, $chromosome_list);

    // Keep track of the y positions for chromosomes, and the center of each chromosome
    // (which is where we'll put the ytick labels)
    $ybase = 0;
    $chrom_ybase = [];
    $chrom_centers = [];

    // Iterate in reverse so that items in the beginning of `$chromosome_list` will
    // appear at the top of the plot
    foreach (array_reverse($chromosome_list) as $chrom) {
        $chrom_ybase[$chrom] = $ybase;
        $chrom_centers[$chrom] = $ybase + $chrom_height / 2.0;
        $ybase += $chrom_height + $chrom_spacing;
    }

    // Colors for different chromosome stains
    $color_lookup = [
        'gneg' => [202 / 255, 202 / 255, 202 / 255],  // background
        'one_chrom' => [0 / 255, 176 / 255, 240 / 255],
        'two_chrom' => [66 / 255, 69 / 255, 121 / 255],
        'centromere' => [1, 1, 1, 0.6],
    ];

    $df = _patch_chromosomal_features($cytobands, $one_chrom_match, $two_chrom_match);

    // Add a new column for colors
    $df['colors'] = $df['gie_stain']->map(function($x) use ($color_lookup) {
        return $color_lookup[$x];
    });

    // Width, height (in inches)
    $figsize = [6.5, 9];

    $fig = plt::figure(['figsize' => $figsize]);
    $ax = $fig->add_subplot(111);

    // Now all we have to do is call our function for the chromosome data...
    foreach (_chromosome_collections($df, $chrom_ybase, $chrom_height) as $collection) {
        $ax->add_collection($collection);
    }

    // Axes tweaking
    $ax->set_yticks(array_map(function($chrom) use ($chrom_centers) {
        return $chrom_centers[$chrom];
    }, $chromosome_list));
    $ax->set_yticklabels($chromosome_list);
    $ax->margins(0.01);
    $ax->axis('tight');

    $handles = [];

    // setup legend
    if (count($one_chrom_match) > 0) {
        $one_chrom_patch = new patches\Patch([
            'color' => $color_lookup['one_chrom'],
            'label' => 'One chromosome shared',
        ]);
        $handles[] = $one_chrom_patch;
    }

    if (count($two_chrom_match) > 0) {
        $two_chrom_patch = new patches\Patch([
            'color' => $color_lookup['two_chrom'],
            'label' => 'Two chromosomes shared',
        ]);
        $handles[] = $two_chrom_patch;
    }

    $no_match_patch = new patches\Patch([
        'color' => $color_lookup['gneg'],
        'label' => 'No shared DNA',
    ]);
    $handles[] = $no_match_patch;

    $centromere_patch = new patches\Patch([
        'color' => [234 / 255, 234 / 255, 234 / 255],
        'label' => 'Centromere',
    ]);
    $handles[] = $centromere_patch;

    plt::legend([
        'handles' => $handles,
        'loc' => 'lower right',
        'bbox_to_anchor' => [0.95, 0.05],
    ]);

    $ax->set_title($title, ['fontsize' => 14, 'fontweight' => 'bold']);
    plt::xlabel('Build ' . $build . ' Chromosome Position', ['fontsize' => 10]);
    logger::info('Saving ' . os::path::relpath($path));
    plt::tight_layout();

    with (new atomic_write($path, ['mode' => 'wb', 'overwrite' => true]))->open() as $f;
        plt::savefig($f);
}

function _chromosome_collections($df, $y_positions, $height, $kwargs) {
    // Yields BrokenBarHCollection of features that can be added to an Axes object.

    $del_width = false;
    if (!in_array('width', $df->columns)) {
        $del_width = true;
        $df['width'] = $df['end'] - $df['start'];
    }

    foreach ($df->groupby('chrom') as $chrom => $group) {
        $yrange = [$y_positions['chr' . $chrom], $height];
        $xranges = $group[['start', 'width']]->values;
        yield new BrokenBarHCollection($xranges, $yrange, [
            'facecolors' => $group['colors'],
        ] + $kwargs);
    }

    if ($del_width) {
        unset($df['width']);
    }
}

function _patch_chromosomal_features($cytobands, $one_chrom_match, $two_chrom_match) {
    // Highlight positions for each chromosome segment / feature.

    $concat = function($df, $chrom, $start, $end, $gie_stain) {
        return pd::concat([
            $df,
            pd::DataFrame([
                'chrom' => [$chrom],
                'start' => [$start],
                'end' => [$end],
                'gie_stain' => [$gie_stain],
            ]),
        ], ['ignore_index' => true]);
    };

    $chromosomes = $cytobands['chrom']->unique();

    $df = pd::DataFrame();

    foreach ($chromosomes as $chromosome) {
        $chromosome_length = max(
            $cytobands[$cytobands['chrom'] == $chromosome]['end']->values
        );

        // get all markers for this chromosome
        $one_chrom_match_markers = $one_chrom_match[$one_chrom_match['chrom'] == $chromosome];
        $two_chrom_match_markers = $two_chrom_match[$two_chrom_match['chrom'] == $chromosome];

        // background of chromosome
        $df = $concat($df, $chromosome, 0, $chromosome_length, 'gneg');

        // add markers for shared DNA on one chromosome
        foreach ($one_chrom_match_markers->itertuples() as $marker) {
            $df = $concat($df, $chromosome, $marker['start'], $marker['end'], 'one_chrom');
        }

        // add markers for shared DNA on both chromosomes
        foreach ($two_chrom_match_markers->itertuples() as $marker) {
            $df = $concat($df, $chromosome, $marker['start'], $marker['end'], 'two_chrom');
        }

        // add centromeres
        foreach ($cytobands[
            ($cytobands['chrom'] == $chromosome) & ($cytobands['gie_stain'] == 'acen')
        ]->itertuples() as $item) {
            $df = $concat($df, $chromosome, $item['start'], $item['end'], 'centromere');
        }
    }

    return $df;
}
