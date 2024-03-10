<?php

namespace Dna\Snps\IO;

use php_user_filter;

class ExtraTabsFilter extends php_user_filter
{
    public function filter($in, $out, int &$consumed, bool $closing): int
    {
        while ($bucket = stream_bucket_make_writeable($in)) {
            $bucket->data = preg_replace('/\t+/', "\t", $bucket->data);
            $consumed += $bucket->datalen;
            stream_bucket_append($out, $bucket);
        }

        return PSFS_PASS_ON;
    }
}
