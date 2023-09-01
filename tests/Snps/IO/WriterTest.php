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

    public function testToCsv()
    {
        $this->runWriterTest("to_csv", "generic_GRCh37.csv");
    }

    public function testToCsvFilename()
    {
        $this->runWriterTest("to_csv", "generic.csv", "generic.csv");
    }

    public function testToTsv()
    {
        $this->runWriterTest("to_tsv", "generic_GRCh37.txt");
    }

    public function testToTsvFilename()
    {
        $this->runWriterTest("to_tsv", "generic.txt", "generic.txt");
    }

    public function testToVcf()
    {
        $this->runWriterTest(
            "to_vcf",
            "vcf_GRCh37.vcf",
            "tests/output/vcf_generic.vcf"
        );
    }

    public function testToVcfFilename()
    {
        $this->runWriterTest("to_vcf", ["filename" => "vcf.vcf", "output_file" => "vcf.vcf"]);
    }

    public function testToVcfChromPrefix()
    {
        $this->runWriterTest(
            "to_vcf",
            [
                "output_file" => "vcf_GRCh37.vcf",
                "expected_output" => "tests/output/vcf_chrom_prefix_chr.vcf",
                "chrom_prefix" => "chr",
            ]
        );
    }

    public function testSaveSnpsFalsePositiveBuild()
    {
        // Create a temporary directory
        $tmpdir = tempnam(sys_get_temp_dir(), 'tmp');
        unlink($tmpdir);
        mkdir($tmpdir);

        // Instantiate SNPs with input file and output directory
        $snps = new SNPs("tests/input/generic.csv", ["output_dir" => $tmpdir]);

        // Define output file path
        $output = $tmpdir . "/generic_GRCh37.txt";

        // Save SNPs to TSV
        $this->assertEquals($output, $snps->toTsv());

        // Modify the output file to add version information
        $s = "";
        $lines = file($output);
        foreach ($lines as $line) {
            if (strpos($line, "snps v") !== false) {
                $s .= "# Generated by snps v1.2.3.post85.dev0+gb386302, https://pypi.org/project/snps/\n";
            } else {
                $s .= $line;
            }
        }

        file_put_contents($output, $s);

        // Run parsing tests on the modified output
        $this->runParsingTests($output, "generic");

        // Clean up
        unlink($output);
        rmdir($tmpdir);
    }

    public function testSaveSnpsVcfFalsePositiveBuild()
    {
        $tmpdir1 = sys_get_temp_dir() . '/tmpdir1';
        mkdir($tmpdir1);

        // Instantiate SNPs with input file and output directory
        $snps = new SNPs("tests/input/testvcf.vcf", ["output_dir" => $tmpdir1]);

        $r = new Resources();
        $r->_reference_sequences["GRCh37"] = [];

        $output = $tmpdir1 . "/vcf_GRCh37.vcf";
        $tmpdir2 = sys_get_temp_dir() . '/tmpdir2';
        mkdir($tmpdir2);

        $dest = $tmpdir2 . "/generic.fa.gz";
        gzip_file("tests/input/generic.fa", $dest);

        $seq = new ReferenceSequence(["ID" => "1", "path" => $dest]);

        $r->_reference_sequences["GRCh37"]["1"] = $seq;

        $this->assertEquals($output, $snps->toVcf());

        $s = "";
        $lines = file($output);
        foreach ($lines as $line) {
            if (strpos($line, "snps v") !== false) {
                $s .= '##source="vcf; snps v1.2.3.post85.dev0+gb386302; https://pypi.org/project/snps/"' . "\n";
            } else {
                $s .= $line;
            }
        }

        file_put_contents($output, $s);

        $this->runParsingTestsVcf($output);

        // Clean up
        unlink($output);
        rmdir($tmpdir1);
        unlink($dest);
        rmdir($tmpdir2);
    }


    public function testSaveSnpsVcfPhased()
    {
        $tmpdir1 = sys_get_temp_dir() . '/tmpdir1';
        mkdir($tmpdir1);

        // Instantiate SNPs with input phased VCF file and output directory
        $s = new SNPs("tests/input/testvcf_phased.vcf", ["output_dir" => $tmpdir1]);

        // Setup resource to use test FASTA reference sequence
        $r = new Resources();
        $r->_reference_sequences["GRCh37"] = [];

        $output = $tmpdir1 . "/vcf_GRCh37.vcf";
        $tmpdir2 = sys_get_temp_dir() . '/tmpdir2';
        mkdir($tmpdir2);

        $dest = $tmpdir2 . "/generic.fa.gz";
        gzip_file("tests/input/generic.fa", $dest);

        $seq = new ReferenceSequence(["ID" => "1", "path" => $dest]);

        $r->_reference_sequences["GRCh37"]["1"] = $seq;

        // Save phased data to VCF
        $this->assertEquals($output, $s->toVcf());

        // Read saved VCF with phased data
        $this->runParsingTestsVcf($output, true);

        // Clean up
        unlink($output);
        rmdir($tmpdir1);
        unlink($dest);
        rmdir($tmpdir2);
    }


    public function testSaveSnpsPhased()
    {
        $tmpdir = sys_get_temp_dir() . '/tmpdir';
        mkdir($tmpdir);

        // Instantiate SNPs with input phased VCF file and output directory
        $s = new SNPs("tests/input/testvcf_phased.vcf", ["output_dir" => $tmpdir]);

        $dest = $tmpdir . "/vcf_GRCh37.txt";

        // Save phased data to TSV
        $this->assertEquals($dest, $s->toTsv());

        // Read saved TSV with phased data
        $this->runParsingTestsVcf($dest, true);

        // Clean up
        unlink($dest);
        rmdir($tmpdir);
    }


    public function runVcfQcTest($expectedOutput, $vcfQcOnly, $vcfQcFilter, $cluster = "c1")
    {
        $tmpdir1 = sys_get_temp_dir() . '/tmpdir1';
        mkdir($tmpdir1);

        // Instantiate SNPs with input CSV file and output directory
        $s = new SNPs("tests/input/generic.csv", ["output_dir" => $tmpdir1]);

        // Setup resource to use test FASTA reference sequence
        $r = new Resources();
        $r->setReferenceSequences(["GRCh37" => []]);

        $output = $tmpdir1 . "/generic_GRCh37.vcf";

        $tmpdir2 = sys_get_temp_dir() . '/tmpdir2';
        mkdir($tmpdir2);
        $dest = $tmpdir2 . "/generic.fa.gz";
        gzipFile("tests/input/generic.fa", $dest);

        $seq = new ReferenceSequence(["ID" => "1", "path" => $dest]);
        $r->getReferenceSequences("GRCh37")["1"] = $seq;

        // Save data to VCF with quality control settings
        $options = ["qc_only" => $vcfQcOnly, "qc_filter" => $vcfQcFilter];
        $this->assertEquals($output, $s->toVcf($options));

        // Read result
        $actual = file_get_contents($output);

        // Read expected result
        $expected = file_get_contents($expectedOutput);

        $this->assertStringContainsString($expected, $actual);

        if (!$vcfQcFilter || !$cluster) {
            $this->assertStringNotContainsString("##FILTER=<ID=lq", $actual);
        }

        // Clean up
        unlink($output);
        unlink($dest);
        rmdir($tmpdir2);
        rmdir($tmpdir1);
    }
}
