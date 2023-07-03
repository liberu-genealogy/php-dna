<?php declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Snps\SNPs;
// use Dna\Utils\gzip_file;
// use Dna\Utils\zip_file;
use PHPUnit\Framework\TestCase;

abstract class BaseSNPsTestCase extends TestCase
{
    public function simulate_snps(
        $chrom = "1",
        $pos_start = 1,
        $pos_max = 248140902,
        $pos_step = 100,
        $genotype = "AA",
        $insert_nulls = true,
        $null_snp_step = 101,
        $complement_genotype_one_chrom = false,
        $complement_genotype_two_chroms = false,
        $complement_snp_step = 50
    ) {
        // Test implementation
    }

    // def run_parsing_tests(
    //     self, file, source, phased=False, build=37, build_detected=False, snps_df=None
    // ):
    //     self.make_parsing_assertions(
    //         self.parse_file(file), source, phased, build, build_detected, snps_df
    //     )
    //     self.make_parsing_assertions(
    //         self.parse_bytes(file), source, phased, build, build_detected, snps_df
    //     )

    //     with tempfile.TemporaryDirectory() as tmpdir:
    //         base = os.path.basename(file)
    //         dest = os.path.join(tmpdir, f"{base}.gz")
    //         gzip_file(file, dest)
    //         self.make_parsing_assertions(
    //             self.parse_file(dest), source, phased, build, build_detected, snps_df
    //         )
    //         self.make_parsing_assertions(
    //             self.parse_bytes(dest), source, phased, build, build_detected, snps_df
    //         )
    //         # remove .gz extension
    //         shutil.move(dest, dest[:-3])
    //         self.make_parsing_assertions(
    //             self.parse_file(dest[:-3]),
    //             source,
    //             phased,
    //             build,
    //             build_detected,
    //             snps_df,
    //         )

    //         dest = os.path.join(tmpdir, f"{base}.zip")
    //         zip_file(file, dest, base)
    //         self.make_parsing_assertions(
    //             self.parse_file(dest), source, phased, build, build_detected, snps_df
    //         )
    //         self.make_parsing_assertions(
    //             self.parse_bytes(dest), source, phased, build, build_detected, snps_df
    //         )
    //         # remove .zip extension
    //         shutil.move(dest, dest[:-4])
    //         self.make_parsing_assertions(
    //             self.parse_file(dest[:-4]),
    //             source,
    //             phased,
    //             build,
    //             build_detected,
    //             snps_df,
    //         )

    public function run_parse_tests(
        $file,
        $source,
        $phased = false,
        $build = 37,
        $build_detected = false,
        $snps_df = null
    ) {
        // $this->make_parsing_assertions(
        //     $this->parse_file($file),
        //     $source,
        //     $phased,
        //     $build,
        //     $build_detected,
        //     $snps_df
        // );

    }

    // def parse_file(self, file, rsids=()):
    //     return SNPs(file, rsids=rsids)

    public function parse_file($file, $rsids = [])
    {
        return new SNPs($file, rsids: $rsids);
    }

}
