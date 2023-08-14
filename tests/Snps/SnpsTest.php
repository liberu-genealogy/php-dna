<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Snps\SNPs;

class SnpsTest extends BaseSNPsTestCase
{
    // private $table;

    protected function setUp(): void
    {
        parent::setUp();
    }

    public static function empty_snps()
    {
        return [new SNPs(), new SNPs(""), new SNPs("tests/input/empty.txt")];
    }

    public function test_len()
    {
        $s = new SNPs("tests/input/generic.csv");
        $this->assertEquals(count($s), 8);
    }

    public function test_len_empty()
    {
        foreach (self::empty_snps() as $s) {
            $this->assertEquals(count($s), 0);
        }
    }

    public function test__toString()
    {
        $s = new SNPs("tests/input/GRCh37.csv");
        $this->assertEquals("SNPs('GRCh37.csv')", $s->__toString());
    }

    public function test__toString_bytes()
    {
        $data = file_get_contents("tests/input/GRCh37.csv");
        $s = new SNPs($data);
        $this->assertEquals("SNPs(<bytes>)", $s->__toString());
    }

    public function testAssembly()
    {
        $s = new SNPs("tests/input/GRCh38.csv");
        $this->assertEquals($s->getAssembly(), "GRCh38");
    }

    public function testAssemblyNoSnps()
    {
        $emptySnps = $this->empty_snps();

        foreach ($emptySnps as $snps) {
            $this->assertEmpty($snps->getAssembly());
        }
    }

    public function testBuild()
    {
        $s = new SNPs("tests/input/NCBI36.csv");
        $this->assertEquals($s->getBuild(), 36);
        $this->assertEquals($s->getAssembly(), "NCBI36");
    }

    public function testBuildDetectedNoSnps()
    {
        $emptySnps = $this->empty_snps();

        foreach ($emptySnps as $snps) {
            $this->assertFalse($snps->isBuildDetected());
        }
    }

    public function testBuildNoSnps()
    {
        $emptySnps = $this->empty_snps();

        foreach ($emptySnps as $snps) {
            $this->assertEmpty($snps->getBuild());
        }
    }

    public function testBuildDetectedPARSnps() 
    {
        $snps = $this->loadAssignPARSnps('tests/input/GRCh37_PAR.csv');
        $this->assertEquals(37, $snps->getBuild());
        $this->assertTrue($snps->isBuildDetected());
        $expectedSnps = $this->snps_GRCh37_PAR();
        $actualSnps = $snps->getSnps();
        $this->assertEquals($expectedSnps, $actualSnps);
    }
      

    public function test_notnull() {
        $s = new SNPs("tests/input/generic.csv");
        $snps = $this->generic_snps();
        unset($snps["rs5"]); // Assuming snps is an associative array
        
        $this->assertEquals($s->notnull(), $snps, "Frames are not equal!");
    }
    
    
    
    
    

}
