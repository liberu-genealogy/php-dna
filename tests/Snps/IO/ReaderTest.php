<?php declare(strict_types=1);

namespace DnaTest\Snps\IO;

use DnaTest\Snps\BaseSNPsTestCase;

final class ReaderTest extends BaseSNPsTestCase
{
    public function testRead23AndMe()
    {
        $this->run_parse_tests("tests/input/23andme.txt", "23andMe");
    }

    public function testRead23AndMeAllele()
    {
        $this->run_parse_tests("tests/input/23andme_allele.txt", "23andMe");
    }

    public function testRead23AndMeWin()
    {
        $this->run_parse_tests("tests/input/23andme_win.txt", "23andMe");
    }

    protected function run_build_detection_test(
        $run_parsing_tests_func,
        $build_str,
        $build_int,
        $file="tests/input/testvcf.vcf",
        $source="vcf",
        $comment_str="##%s\n",
        $insertion_line=1
    ) {
        $s = "";
        $lines = file($file);
        foreach ($lines as $i => $line) {
            $s .= $line;
            if ($i == $insertion_line) {
                $s .= sprintf($comment_str, $build_str);
            }
        }

        $file_build_comment = tempnam(sys_get_temp_dir(), basename($file));
        file_put_contents($file_build_comment, $s);

        call_user_func(
            $run_parsing_tests_func,
            $file_build_comment,
            $source,
            $build_int,
            true
        );
    }

    public function testRead23AndMeBuild36()
    {
        $this->run_build_detection_test(
            array($this, 'run_parse_tests'),
            "build 36",
            36,
            "tests/input/23andme.txt",
            "23andMe",
            "# %s\n"
        );
    }

   
    public function testRead23AndMeBuild37()
    {
        $this->run_build_detection_test(
            array($this, 'run_parse_tests'),
            "build 37",
            37,
            "tests/input/23andme.txt",
            "23andMe",
            "# %s\n"
        );
    }

    public function testRead23AndMeBuild38()
    {
        $this->run_build_detection_test(
            array($this, 'run_parse_tests'),
            "build 38",
            38,
            "tests/input/23andme.txt",
            "23andMe",
            "# %s\n"
        );
    }

    public function testReadAncestry()
    {
        // https://www.ancestry.com
        $this->run_parse_tests("tests/input/ancestry.txt", "AncestryDNA");
    }

    public function testReadAncestryExtraTab()
    {
        $total_snps = 100;
        $s = "#AncestryDNA\r\n";
        $s .= "rsid\tchromosome\tposition\tallele1\tallele2\r\n";
        // add extra tab separator in first line
        $s .= "rs1\t1\t101\t\tA\tA\r\n";
        // generate remainder of lines
        for ($i = 1; $i < $total_snps; $i++) {
            $s .= "rs" . (1 + $i) . "\t1\t" . (101 + $i) . "\tA\tA\r\n";
        }

        $snps_df = $this->create_snp_df(
            array_map(function ($i) {
                return "rs" . (1 + $i);
            }, range(0, $total_snps)),
            "1",
            array_map(function ($i) {
                return 101 + $i;
            }, range(0, $total_snps)),
            "AA"
        );

        $path = tempnam(sys_get_temp_dir(), "ancestry_extra_tab.txt");
        file_put_contents($path, $s);

        $this->run_parse_tests($path, "AncestryDNA", $snps_df);
    }


}