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
}