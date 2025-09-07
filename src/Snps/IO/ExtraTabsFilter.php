<?php

declare(strict_types=1);

namespace Dna\Snps\IO;

use php_user_filter;

/**
 * Stream filter to handle extra tabs in CSV/TSV files
 */
class ExtraTabsFilter extends php_user_filter
{
    public function filter($in, $out, &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            // Replace multiple consecutive tabs with single tabs
            $bucket->data = preg_replace('/\t+/', "\t", $bucket->data);

            // Remove trailing tabs at end of lines
            $bucket->data = preg_replace('/\t+\n/', "\n", $bucket->data);
            $bucket->data = preg_replace('/\t+\r\n/', "\r\n", $bucket->data);

            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}