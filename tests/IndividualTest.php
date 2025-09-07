<?php

declare(strict_types=1);

namespace DnaTest;

use PHPUnit\Framework\TestCase;
use Dna\Individual;

class IndividualTest extends TestCase
{
    public function testIndividualCreation(): void
    {
        $individual = new Individual('John Doe');

        $this->assertEquals('John Doe', $individual->getName());
        $this->assertEquals("Individual('John Doe')", (string)$individual);
    }

    public function testVarNameGeneration(): void
    {
        $individual = new Individual('John Doe-Smith');
        $varName = $individual->getVarName();

        // Should convert special characters to underscores
        $this->assertEquals('John_Doe_Smith', $varName);
    }

    public function testVarNameWithNumbers(): void
    {
        $individual = new Individual('123Test');
        $varName = $individual->getVarName();

        // Should prefix with 'var_' if starts with number
        $this->assertEquals('var_123Test', $varName);
    }

    public function testVarNameWithSpecialCharacters(): void
    {
        $individual = new Individual('Test@#$%Name');
        $varName = $individual->getVarName();

        // Should replace special characters with underscores
        $this->assertEquals('Test_Name', $varName);
    }

    public function testVarNameEmpty(): void
    {
        $individual = new Individual('');
        $varName = $individual->getVarName();

        // Should return 'unnamed' for empty string
        $this->assertEquals('unnamed', $varName);
    }

    public function testVarNameOnlySpecialChars(): void
    {
        $individual = new Individual('@#$%');
        $varName = $individual->getVarName();

        // Should return 'unnamed' when only special characters
        $this->assertEquals('unnamed', $varName);
    }

    public function testIndividualWithRawData(): void
    {
        $rawData = [
            'rs123' => ['rsid' => 'rs123', 'chrom' => '1', 'pos' => 1000, 'genotype' => 'AA'],
            'rs456' => ['rsid' => 'rs456', 'chrom' => '2', 'pos' => 2000, 'genotype' => 'AT'],
        ];

        $individual = new Individual('Test Individual', $rawData);

        $this->assertEquals('Test Individual', $individual->getName());
        $this->assertTrue($individual->isValid());
        $this->assertEquals(2, $individual->count());
    }
}