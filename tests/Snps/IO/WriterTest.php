<?php

use Dna\Resources;
use Dna\Snps\ReferenceSequence;
use Dna\Snps\SNPs;
use DnaTest\Snps\BaseSNPsTestCase;

final class WriterTest extends BaseSNPsTestCase
{

    protected function runWriterTest($funcStr, $filename = "", $outputFile = "", $expectedOutput = "", $kwargs)
    {
        if ($funcStr === "to_vcf") {
            $tmpdir1 = sys_get_temp_dir() . '/' . uniqid();
            mkdir($tmpdir1);

            $s = new SNPs("tests/input/testvcf.vcf", output_dir: $tmpdir1);

            $r = new Resources();
            $r->_reference_sequences["GRCh37"] = [];

            $output = $tmpdir1 . '/' . $outputFile;
            $tmpdir2 = sys_get_temp_dir() . '/' . uniqid();
            mkdir($tmpdir2);

            $dest = $tmpdir2 . '/generic.fa.gz';
            gzip_file("tests/input/generic.fa", $dest);

            $seq = new ReferenceSequence(
                "1",
                "",
                $dest
            );

            $r->_reference_sequences["GRCh37"]["1"] = $seq;

            if (!$filename) {
                $result = $s->{$funcStr}($kwargs);
            } else {
                $result = $s->{$funcStr}($filename, $kwargs);
            }

            $this->assertSame($result, $output);

            if ($expectedOutput) {
                // Read result
                $actual = file_get_contents($output);

                // Read expected result
                $expected = file_get_contents($expectedOutput);

                $this->assertStringContainsString($expected, $actual);
            }


            $this->runParsingTestsVcf($output);
        } else {
            $tmpdir = sys_get_temp_dir() . '/' . uniqid();
            mkdir($tmpdir);

            $snps = new SNPs("tests/input/generic.csv", output_dir: $tmpdir);
            $dest = $tmpdir . '/' . $outputFile;

            if (!$filename) {
                $this->assertSame($snps->{$funcStr}(), $dest);
            } else {
                $this->assertSame($snps->{$funcStr}($filename), $dest);
            }


            $this->run_parse_tests($dest, "generic");
        }
    }
}
