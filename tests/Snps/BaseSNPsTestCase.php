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

    // @staticmethod
    // def create_snp_df(rsid, chrom, pos, genotype):
    //     df = pd.DataFrame(
    //         {"rsid": rsid, "chrom": chrom, "pos": pos, "genotype": genotype},
    //         columns=["rsid", "chrom", "pos", "genotype"],
    //     )
    //     df.rsid = df.rsid.astype(object)
    //     df.chrom = df.chrom.astype(object)
    //     df.pos = df.pos.astype(np.uint32)
    //     df.genotype = df.genotype.astype(object)
    //     df = df.set_index("rsid")
    //     return df
    public static function create_snp_df(
        mixed $rsid, 
        mixed $chrom, 
        mixed $pos, 
        mixed $genotype)
    {
        $df = [
            ["rsid" => $rsid, "chrom" => $chrom, "pos" => $pos, "genotype" => $genotype],
        ];
        return $df;
    }

    // def generic_snps(self):
    //     return self.create_snp_df(
    //         rsid=["rs" + str(i) for i in range(1, 9)],
    //         chrom=["1"] * 8,
    //         pos=list(range(101, 109)),
    //         genotype=["AA", "CC", "GG", "TT", np.nan, "GC", "TC", "AT"],
    //     )

    protected function generic_snps()
    {
        $rsid = [];
        for ($i = 1; $i <= 8; $i++) {
            $rsid[] = "rs" . $i;
        }
        return $this->create_snp_df(
            rsid: $rsid,
            chrom: array_fill(0, 8, "1"),
            pos: range(101, 109),
            genotype: ["AA", "CC", "GG", "TT", null, "GC", "TC", "AT"],
        );
    }


    public function run_parse_tests(
        $file,
        $source,
        $phased = false,
        $build = 37,
        $build_detected = false,
        $snps_df = null
    ) {
        $this->make_parsing_assertions(
            $this->parse_file($file),
            $source,
            $phased,
            $build,
            $build_detected,
            $snps_df
        );

    }

    // def make_parsing_assertions(
    //     self, snps, source, phased, build, build_detected, snps_df
    // ):
    //     if snps_df is None:
    //         snps_df = self.generic_snps()

    //     # these are useful for debugging if there is a problem
    //     print("Observed:")
    //     print(snps.snps)
    //     print(snps.snps.info())
    //     print("Expected:")
    //     print(snps_df)
    //     print(snps_df.info())

    //     self.assertEqual(snps.source, source)
    //     pd.testing.assert_frame_equal(snps.snps, snps_df, check_exact=True)
    //     self.assertTrue(snps.phased) if phased else self.assertFalse(snps.phased)
    //     self.assertEqual(snps.build, build)
    //     self.assertTrue(snps.build_detected) if build_detected else self.assertFalse(
    //         snps.build_detected
    //     )
    //     self.make_normalized_dataframe_assertions(snps.snps)

    public function make_parsing_assertions(
        $snps,
        $source,
        $phased,
        $build,
        $build_detected,
        $snps_df
    ) {
        if (empty($snps_df))
            $snps_df = $this->generic_snps();
        //     snps_df = self.generic_snps()

        // # these are useful for debugging if there is a problem
        // print("Observed:")
        // print(snps.snps)
        // print(snps.snps.info())
        // print("Expected:")
        // print(snps_df)
        // print(snps_df.info())

        // self.assertEqual(snps.source, source)
        // pd.testing.assert_frame_equal(snps.snps, snps_df, check_exact=True)
        // self.assertTrue(snps.phased) if phased else self.assertFalse(snps.phased)
        // self.assertEqual(snps.build, build)
        // self.assertTrue(snps.build_detected) if build_detected else self.assertFalse(
        //     snps.build_detected
        // )
        // self.make_normalized_dataframe_assertions(snps.snps)
    }

    public function parse_file($file, $rsids = [])
    {
        return new SNPs($file, rsids: $rsids);
    }

}
