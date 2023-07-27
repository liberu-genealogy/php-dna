<?php declare(strict_types=1);

namespace DnaTest\Snps\IO;

use Dna\Resources;
use Dna\Snps\SNPsResources;
use Dna\Snps\Utils;
use DnaTest\Snps\BaseSNPsTestCase;

final class ReaderTest extends BaseSNPsTestCase
{

    public static function setupGsaTest($resourcesDir) {
        // reset resource if already loaded
        $r = new Resources(
            resources_dir: $resourcesDir,
        );
        $r->init_resource_attributes();

        Utils::gzip_file(
            "tests/resources/gsa_rsid_map.txt",
            $resourcesDir . "/gsa_rsid_map.txt.gz"
        );
        Utils::gzip_file(
            "tests/resources/gsa_chrpos_map.txt",
            $resourcesDir . "/gsa_chrpos_map.txt.gz"
        );
        Utils::gzip_file(
            "tests/resources/dbsnp_151_37_reverse.txt",
            $resourcesDir . "/dbsnp_151_37_reverse.txt.gz"
        );
    }

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

        $this->run_parse_tests($path, "AncestryDNA", snps_df: $snps_df);
    }

    public function testReadAncestryMultiSep()
    {
        // https://www.ancestry.com
        $this->run_parse_tests("tests/input/ancestry_multi_sep.txt", "AncestryDNA");
    }

    // def test_read_codigo46(self):
    //     # https://codigo46.com.mx
    //     with tempfile.TemporaryDirectory() as tmpdir:
    //         self._setup_gsa_test(tmpdir)
    //         self.run_parsing_tests("tests/input/codigo46.txt", "Codigo46")
    //         self._teardown_gsa_test()

    public function testReadCodigo46()
    {
        // https://codigo46.com.mx
        $this->run_parse_tests("tests/input/codigo46.txt", "Codigo46");
    }

    // def test_read_tellmeGen(self):
    //     # https://www.tellmegen.com/
    //     with tempfile.TemporaryDirectory() as tmpdir:
    //         self._setup_gsa_test(tmpdir)
    //         self.run_parsing_tests("tests/input/tellmeGen.txt", "tellmeGen")
    //         self._teardown_gsa_test()

    // def test_read_DNALand(self):
    //     # https://dna.land/
    //     self.run_parsing_tests("tests/input/DNALand.txt", "DNA.Land")

    // def test_read_circledna(self):
    //     # https://circledna.com/
    //     df = self.generic_snps()
    //     df.drop("rs5", inplace=True)  # only called genotypes
    //     self.run_parsing_tests("tests/input/circledna.txt", "CircleDNA", snps_df=df)

    // def test_read_ftdna(self):
    //     # https://www.familytreedna.com
    //     self.run_parsing_tests("tests/input/ftdna.csv", "FTDNA")

    // def test_read_ftdna_concat_gzip_extra_data(self):
    //     # https://www.familytreedna.com

    //     total_snps1 = 10
    //     total_snps2 = 10
    //     # generate content of first file
    //     s1 = "RSID,CHROMOSOME,POSITION,RESULT\r\n"
    //     for i in range(0, total_snps1):
    //         s1 += f'"rs{1 + i}","1","{101 + i}","AA"\r\n'

    //     # generate content of second file
    //     s2 = "RSID,CHROMOSOME,POSITION,RESULT\r\n"
    //     for i in range(0, total_snps2):
    //         s2 += f'"rs{total_snps1 + 1 + i}","1","{ total_snps1 + 101 + i}","AA"\r\n'
    //     snps_df = self.create_snp_df(
    //         rsid=[f"rs{1 + i}" for i in range(0, total_snps1 + total_snps2)],
    //         chrom="1",
    //         pos=[101 + i for i in range(0, total_snps1 + total_snps2)],
    //         genotype="AA",
    //     )

    //     with tempfile.TemporaryDirectory() as tmpdir:
    //         file1 = os.path.join(tmpdir, "ftdna_concat_gzip1.csv")
    //         file1_gz = f"{file1}.gz"
    //         file2 = os.path.join(tmpdir, "ftdna_concat_gzip2.csv")
    //         file2_gz = f"{file2}.gz"
    //         path = os.path.join(tmpdir, "ftdna_concat_gzip.csv.gz")

    //         # write individual files
    //         with open(file1, "w") as f:
    //             f.write(s1)
    //         with open(file2, "w") as f:
    //             f.write(s2)

    //         # compress files
    //         gzip_file(file1, file1_gz)
    //         gzip_file(file2, file2_gz)

    //         # concatenate gzips
    //         with open(file1_gz, "rb") as f:
    //             data = f.read()
    //         with open(file2_gz, "rb") as f:
    //             data += f.read()

    //         # add extra data
    //         data += b"extra data"

    //         # write file with concatenated gzips and extra data
    //         with open(path, "wb") as f:
    //             f.write(data)

    //         self.make_parsing_assertions(
    //             self.parse_file(path), "FTDNA", False, 37, False, snps_df
    //         )
    //         self.make_parsing_assertions(
    //             self.parse_bytes(path), "FTDNA", False, 37, False, snps_df
    //         )

    // def test_read_ftdna_famfinder(self):
    //     # https://www.familytreedna.com
    //     self.run_parsing_tests("tests/input/ftdna_famfinder.csv", "FTDNA")

    // def test_read_ftdna_second_header(self):
    //     # https://www.familytreedna.com

    //     total_snps1 = 100
    //     total_snps2 = 10
    //     s = "RSID,CHROMOSOME,POSITION,RESULT\n"
    //     # generate first chunk of lines
    //     for i in range(0, total_snps1):
    //         s += f'"rs{1 + i}","1","{101 + i}","AA"\n'
    //     # add second header
    //     s += "RSID,CHROMOSOME,POSITION,RESULT\n"
    //     # generate second chunk of lines
    //     for i in range(0, total_snps2):
    //         s += f'"rs{total_snps1 + 1 + i}","1","{total_snps1 + 101 + i}","AA"\n'

    //     snps_df = self.create_snp_df(
    //         rsid=[f"rs{1 + i}" for i in range(0, total_snps1 + total_snps2)],
    //         chrom="1",
    //         pos=[101 + i for i in range(0, total_snps1 + total_snps2)],
    //         genotype="AA",
    //     )

    //     with tempfile.TemporaryDirectory() as tmpdir:
    //         path = os.path.join(tmpdir, "ftdna_second_header.txt")
    //         with open(path, "w") as f:
    //             f.write(s)

    //         self.run_parsing_tests(path, "FTDNA", snps_df=snps_df)


}