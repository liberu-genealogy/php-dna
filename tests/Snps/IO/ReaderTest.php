<?php declare(strict_types=1);

namespace DnaTest\Snps\IO;

use DnaTest\Snps\BaseSNPsTestCase;

final class ReaderTest extends BaseSNPsTestCase
{

    // def test_read_23andme(self):
    //     # https://www.23andme.com
    //     self.run_parsing_tests("tests/input/23andme.txt", "23andMe")
    public function testRead23AndMe()
    {
        $this->run_parse_tests("tests/input/23andme.txt", "23andMe");
    }

    public function testRead23AndMeAllele()
    {
        $this->run_parse_tests("tests/input/23andme_allele.txt", "23andMe");
    }

    // def test_read_23andme_win(self):
    //     # https://www.23andme.com
    //     # windows line endings
    //     self.run_parsing_tests("tests/input/23andme_win.txt", "23andMe")

    // def test_read_23andme_build36(self):
    //     self.run_build_detection_test(
    //         self.run_parsing_tests,
    //         "build 36",
    //         36,
    //         file="tests/input/23andme.txt",
    //         source="23andMe",
    //         comment_str="# {}\n",
    //     )

    // def test_read_23andme_build37(self):
    //     self.run_build_detection_test(
    //         self.run_parsing_tests,
    //         "build 37",
    //         37,
    //         file="tests/input/23andme.txt",
    //         source="23andMe",
    //         comment_str="# {}\n",
    //     )

    // def test_read_ancestry(self):
    //     # https://www.ancestry.com
    //     self.run_parsing_tests("tests/input/ancestry.txt", "AncestryDNA")

    // def test_read_ancestry_extra_tab(self):
    //     # https://www.ancestry.com

    //     total_snps = 100
    //     s = "#AncestryDNA\r\n"
    //     s += "rsid\tchromosome\tposition\tallele1\tallele2\r\n"
    //     # add extra tab separator in first line
    //     s += "rs1\t1\t101\t\tA\tA\r\n"
    //     # generate remainder of lines
    //     for i in range(1, total_snps):
    //         s += f"rs{1 + i}\t1\t{101 + i}\tA\tA\r\n"

    //     snps_df = self.create_snp_df(
    //         rsid=[f"rs{1 + i}" for i in range(0, total_snps)],
    //         chrom="1",
    //         pos=[101 + i for i in range(0, total_snps)],
    //         genotype="AA",
    //     )

    //     with tempfile.TemporaryDirectory() as tmpdir:
    //         path = os.path.join(tmpdir, "ancestry_extra_tab.txt")
    //         with open(path, "w") as f:
    //             f.write(s)

    //         self.run_parsing_tests(path, "AncestryDNA", snps_df=snps_df)


}