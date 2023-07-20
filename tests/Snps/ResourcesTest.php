<?php

declare(strict_types=1);

namespace DnaTest\Snps;

use Dna\Resources;
use Dna\Snps\EnsemblRestClient;
use Dna\Snps\ReferenceSequence;
use Dna\Snps\SNPs;
use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\Psr7\Response;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Psr7\Request;
use PHPUnit\Framework\TestResult;
use Psr\Http\Message\UriInterface;
use ReflectionClass;

class ResourcesTest extends BaseSNPsTestCase
{
    private Resources $resource;
    private $downloads_enabled = false;
    private $originalHttp = null;

    private function _reset_resource()
    {
        $this->resource->init_resource_attributes();
    }

    public function setUp($result = null): void
    {
        // Set resources directory based on if downloads are being performed
        // https://stackoverflow.com/a/11180583
        $this->resource = new Resources();
        $this->originalHttp = $this->resource->getHttpClient();
        $this->_reset_resource();
        if ($this->downloads_enabled) {
            $this->resource->setResourcesDir("./resources");
        } else {
            // Use a temporary directory for test resource data
            $tmpdir = sys_get_temp_dir();
            $this->resource->setResourcesDir($tmpdir);
        }
        parent::setUp($result);
    }

    public function tearDown(): void
    {
        $this->_reset_resource();
        unset($this->resource);
    }

    public function testGetAssemblyMappingData(): void
    {
        $f = function () {
            $effects = array_fill(0, 25, ["mappings" => []]);
            foreach ($this->NCBI36_GRCh37() as $k => $v) {
                $effects[intval($k) - 1] = $v;
            }
            $mock = $this->getMockBuilder(EnsemblRestClient::class)
                ->disableOriginalConstructor()
                ->getMock();
            $mock->expects($this->any())
                ->method("perform_rest_action")
                ->will($this->onConsecutiveCalls(...$effects));

            $this->resource->setRestClient($mock);
            return $this->resource->getAssemblyMappingData("NCBI36", "GRCh37");
        };

        $assembly_mapping_data = ($this->downloads_enabled) ?
            $this->resource->getAssemblyMappingData("NCBI36", "GRCh37") :
            $f();

        $this->assertCount(25, $assembly_mapping_data);
    }

    public function testGetGsaResources(): void
    {
        $f = function () {
            // mock download of test data for each resource
            $this->_generateTestGsaResources();
            // load test resources saved to `tmpdir`
            return $this->resource->getGsaResources();
        };

        $gsa_resources = ($this->downloads_enabled) ?
            $this->resource->getGsaResources() :
            $f();

        $this->assertCount(618540, $gsa_resources["rsid_map"]);
        $this->assertCount(665608, $gsa_resources["chrpos_map"]);
        $this->assertCount(2393418, $gsa_resources["dbsnp_151_37_reverse"]);
    }

    protected function _generateTestGsaResources(): void
    {
        $s = "Name\tRsID\n";
        for ($i = 1; $i <= 618540; $i++) {
            $s .= "rs{$i}\trs{$i}\n";
        }

        $mockResponse = new Response(200, [], $s);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);
        $this->resource->getGsaRsid();

        $s = "Name\tChr\tMapInfo\tdeCODE(cM)\n";
        for ($i = 1; $i <= 665608; $i++) {
            $s .= "rs{$i}\t1\t{$i}\t0.0000\n";
        }

        $mockResponse = new Response(200, [], $s);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);
        $this->resource->getGsaChrpos();

        $s = "# comment\n";
        $s .= "rs1 0.0 0.0 0.0 0.0\n";
        for ($i = 2; $i <= 2393418; $i++) {
            $s .= "rs{$i}\n";
        }

        $mockResponse = new Response(200, [], $s);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);
        $this->resource->get_dbsnp_151_37_reverse();
    }


    public function testGetAllResources()
    {
        $f = function () {
            // mock download of test data for each resource
            $this->_generateTestGsaResources();
            $this->_generate_test_chip_clusters();
            $this->_generate_test_low_quality_snps();

            // generate test data for permutations of remapping data
            $effects = array_fill(0, 25, array("mappings" => array()));
            foreach ($this->NCBI36_GRCh37() as $k => $v) {
                $effects[(int)$k - 1] = $v;
            }

            $mock = $this->getMockBuilder(EnsemblRestClient::class)
                ->getMock();
            $mock->method("perform_rest_action")
                ->willReturnOnConsecutiveCalls(...array_fill(0, 6, $effects));

            $this->resource->setRestClient($mock);
            return $this->resource->getAllResources();
        };

        $resources = $this->downloads_enabled ? $this->resource->getAllResources() : $f();
        foreach ($resources as $k => $v) {
            $this->assertGreaterThanOrEqual(0, count($v));
        }
    }

    protected function _generate_test_chip_clusters()
    {
        $responseContent = "1:1\tc1\n" . str_repeat("1:1\tc1\n", 2135213);

        $mockResponse = new Response(200, [], $responseContent);
        $httpClient = $this->createMockHttpClient([$mockResponse], true);
        $this->resource->setHttpClient($httpClient);

        return $this->resource->get_chip_clusters();
    }

    public function testGetChipClusters()
    {
        $chip_clusters = $this->downloads_enabled
            ? $this->resource->get_chip_clusters()
            : $this->_generate_test_chip_clusters();

        $this->assertEquals(2135214, count($chip_clusters));
    }

    protected function _generate_test_low_quality_snps()
    {
        $mockResponseContent = "c1\t" . str_repeat("1:1,", 56024) . "1:1\n";

        $mockResponse = new Response(200, ['Content-Encoding' => 'gzip'], gzencode($mockResponseContent));
        $httpClient = $this->createMockHttpClient([$mockResponse]);
        $this->resource->setHttpClient($httpClient);

        return $this->resource->getLowQualitySNPs();
    }

    public function testGetLowQualitySNPs()
    {
        $f = function () {
            return $this->_generate_test_low_quality_snps();
            // return $this->resource->getLowQualitySNPs();
        };

        $lowQualitySnps = ($this->downloads_enabled) ? $this->resource->getLowQualitySNPs() : $f();

        $this->assertEquals(56025, count($lowQualitySnps));
    }

    // make public to use in test
    protected function testDownloadExampleDatasets()
    {
        $f = function () {
            $mockResponse = new Response(200, [], '');
            $httpClient = $this->createMockHttpClient([$mockResponse]);
            $this->resource->setHttpClient($httpClient);
            return $this->resource->download_example_datasets();
        };

        $paths = ($this->downloads_enabled) ? $this->resource->download_example_datasets() : $f();

        foreach ($paths as $path) {
            if (empty($path) || !file_exists($path)) {
                echo "Example dataset(s) not currently available\n";
                return;
            }
        }
    }

    public function testGetPathsReferenceSequencesInvalidAssembly()
    {
        [$assembly, $chroms, $urls, $paths] = $this->resource->getPathsReferenceSequences(
            assembly: "36"
        );

        $chroms = $chroms;
        $urls = $urls;
        $paths = $paths;

        $this->assertEmpty($assembly);
        $this->assertEmpty($chroms);
        $this->assertEmpty($urls);
        $this->assertEmpty($paths);
    }

    protected function runReferenceSequencesTest(callable $f, string $assembly = "GRCh37")
    {
        if ($this->downloads_enabled) {
            $f();
        } else {
            $s = ">MT dna:chromosome chromosome:{$assembly}:MT:1:16569:1 REF\n";
            for ($i = 0; $i < 276; $i++) {
                $s .= str_repeat("A", 60);
                $s .= "\n";
            }
            $s .= str_repeat("A", 9);
            $s .= "\n";

            $mockResponse = new Response(200, ['Content-Encoding' => 'gzip'], gzcompress($s));
            $httpClient = $this->createMockHttpClient([$mockResponse]);
            $this->resource->setHttpClient($httpClient);

            $f();
        }
    }

    protected function runCreateReferenceSequencesTest(string $assemblyExpect, string $urlExpect)
    {
        $f = function () use ($assemblyExpect, $urlExpect) {
            [$assembly, $chroms, $urls, $paths] = $this->resource->getPathsReferenceSequences(
                assembly: $assemblyExpect,
                chroms: ["MT"]
            );
            $seqs = $this->resource->create_reference_sequences($assembly, $chroms, $urls, $paths);

            $this->assertCount(1, $seqs);
            $this->assertEquals($seqs["MT"]->__toString(), "ReferenceSequence(assembly={$assemblyExpect}, ID=MT)");
            $this->assertEquals($seqs["MT"]->getID(), "MT");
            $this->assertEquals($seqs["MT"]->getChrom(), "MT");
            $this->assertEquals($seqs["MT"]->getUrl(), $urlExpect);
            $this->assertEquals($seqs["MT"]->getPath(), $this->resource->relativePathToSubdir(
                "fasta",
                assembly: $assemblyExpect,
                filename: basename($urlExpect)
            ));
            $this->assertTrue(file_exists($seqs["MT"]->getPath()));
            $this->assertEquals($seqs["MT"]->getAssembly(), $assemblyExpect);
            $this->assertEquals($seqs["MT"]->getBuild(), "B" . substr($assemblyExpect, -2));
            $this->assertEquals($seqs["MT"]->getSpecies(), "Homo sapiens");
            $this->assertEquals($seqs["MT"]->getTaxonomy(), "x");
        };

        $this->runReferenceSequencesTest($f, $assemblyExpect);
    }

    public function testCreateReferenceSequencesNCBI36()
    {
        $this->runCreateReferenceSequencesTest(
            "NCBI36",
            "ftp://ftp.ensembl.org/pub/release-54/fasta/homo_sapiens/dna/Homo_sapiens.NCBI36.54.dna.chromosome.MT.fa.gz"
        );
    }

    public function testCreateReferenceSequencesGRCh37()
    {
        $this->runCreateReferenceSequencesTest(
            "GRCh37",
            "ftp://ftp.ensembl.org/pub/grch37/release-96/fasta/homo_sapiens/dna/Homo_sapiens.GRCh37.dna.chromosome.MT.fa.gz"
        );
    }

    public function testCreateReferenceSequencesGRCh38()
    {
        $this->runCreateReferenceSequencesTest(
            "GRCh38",
            "ftp://ftp.ensembl.org/pub/release-96/fasta/homo_sapiens/dna/Homo_sapiens.GRCh38.dna.chromosome.MT.fa.gz"
        );
    }

    public function testCreateReferenceSequencesInvalidPath()
    {
        $this->runReferenceSequencesTest(function () {
            list($assembly, $chroms, $urls, $paths) = $this->resource->getPathsReferenceSequences(
                assembly: "GRCh37",
                chroms: ["MT"]
            );
            $paths[0] = "";
            $seqs = $this->resource->create_reference_sequences($assembly, $chroms, $urls, $paths);
            $this->assertCount(0, $seqs);
        });
    }

    public function testDownloadFileSocketTimeout()
    {
        $mockHandler = new MockHandler([
            new RequestException(
                "Request timeout",
                new Request('GET', 'http://url'),
                previous: new \Exception("Timeout")
            ),
        ]);
        $httpClient = new Client([
            'handler' => $mockHandler,
            'http_errors' => false,
        ]);
        $this->resource->setHttpClient($httpClient);

        $path = $this->resource->download_file("http://url", "test.txt");
        $this->assertEquals("", $path);
    }

    public function testDownloadFileURLError()
    {
        $httpClient = new Client([
            'handler' => function (Request $request, array $options) {
                $url = $request->getUri();
                if ($url instanceof UriInterface) {
                    $url = $url->__toString();
                }
                if (strpos($url, 'http://') === 0) {
                    throw new RequestException("test error", $request);
                }
                if (strpos($url, 'ftp://') === 0) {
                    throw new RequestException("test error", $request);
                }
            },
            'http_errors' => false,
        ]);
        $this->resource->setHttpClient($httpClient);

        $path1 = $this->resource->download_file("http://url", "test.txt");
        $path2 = $this->resource->download_file("ftp://url", "test.txt");

        $this->assertEquals("", $path1);
        $this->assertEquals("", $path2);
    }

    public function testGetReferenceSequences()
    {
        $this->runReferenceSequencesTest(function () {
            $seqs = $this->resource->getReferenceSequences(
                chroms: ['MT']
            );
            $this->assertCount(1, $seqs);
            $this->assertEquals(
                $seqs['MT']->__toString(),
                "ReferenceSequence(assembly=GRCh37, ID=MT)"
            );
            $this->assertEquals($seqs['MT']->getID(), 'MT');
            $this->assertEquals($seqs['MT']->getChrom(), 'MT');
            $this->assertEquals(
                $seqs['MT']->getUrl(),
                'ftp://ftp.ensembl.org/pub/grch37/release-96/fasta/homo_sapiens/dna/Homo_sapiens.GRCh37.dna.chromosome.MT.fa.gz'
            );
            $this->assertEquals(
                $seqs['MT']->getPath(),
                $this->resource->relativePathToSubdir('fasta', 'GRCh37', 'Homo_sapiens.GRCh37.dna.chromosome.MT.fa.gz')
            );
            $this->assertTrue(file_exists($seqs['MT']->getPath()));
            $this->assertEquals($seqs['MT']->getAssembly(), 'GRCh37');
            $this->assertEquals($seqs['MT']->getBuild(), 'B37');
            $this->assertEquals($seqs['MT']->getSpecies(), 'Homo sapiens');
            $this->assertEquals($seqs['MT']->getTaxonomy(), 'x');
        });
    }

    public function testGetAllReferenceSequences()
    {
        $this->runReferenceSequencesTest(function () {
            $seqs = $this->resource->getAllReferenceSequences(['MT']);
            $this->assertCount(3, $seqs);

            $this->assertCount(1, $seqs['NCBI36']);
            $this->assertEquals(
                $seqs['NCBI36']['MT']->getPath(),
                $this->resource->relativePathToSubdir('fasta', 'NCBI36', 'Homo_sapiens.NCBI36.54.dna.chromosome.MT.fa.gz')
            );

            $this->assertCount(1, $seqs['GRCh37']);
            $this->assertEquals(
                $seqs['GRCh37']['MT']->getPath(),
                $this->resource->relativePathToSubdir('fasta', 'GRCh37', 'Homo_sapiens.GRCh37.dna.chromosome.MT.fa.gz')
            );

            $this->assertCount(1, $seqs['GRCh38']);
            $this->assertEquals(
                $seqs['GRCh38']['MT']->getPath(),
                $this->resource->relativePathToSubdir('fasta', 'GRCh38', 'Homo_sapiens.GRCh38.dna.chromosome.MT.fa.gz')
            );
        });
    }

    public function testGetReferenceSequencesInvalidAssembly()
    {
        $seqs = $this->resource->getReferenceSequences(assembly: '36');
        $this->assertCount(0, $seqs);
    }

    public function testGetReferenceSequencesChromNotAvailable()
    {
        $this->runReferenceSequencesTest(function () {
            $this->resource->getReferenceSequences(chroms: ['MT']);
            unset($this->resource->getReferenceSequences()['GRCh37']['MT']);
            $seqs = $this->resource->getReferenceSequences(chroms: ['MT']);
            $this->assertCount(1, $seqs);
            $this->assertEquals(
                $seqs['MT']->__toString(),
                "ReferenceSequence(assembly=GRCh37, ID=MT)"
            );
            $this->assertEquals($seqs['MT']->getID(), 'MT');
            $this->assertEquals($seqs['MT']->getChrom(), 'MT');
            $this->assertEquals(
                $seqs['MT']->getUrl(),
                'ftp://ftp.ensembl.org/pub/grch37/release-96/fasta/homo_sapiens/dna/Homo_sapiens.GRCh37.dna.chromosome.MT.fa.gz'
            );
            $this->assertEquals(
                $seqs['MT']->getPath(),
                $this->resource->relativePathToSubdir('fasta', 'GRCh37', 'Homo_sapiens.GRCh37.dna.chromosome.MT.fa.gz')
            );
            $this->assertTrue(file_exists($seqs['MT']->getPath()));
            $this->assertEquals($seqs['MT']->getAssembly(), 'GRCh37');
            $this->assertEquals($seqs['MT']->getBuild(), 'B37');
            $this->assertEquals($seqs['MT']->getSpecies(), 'Homo sapiens');
            $this->assertEquals($seqs['MT']->getTaxonomy(), 'x');
        });
    }

    protected function runReferenceSequenceLoadSequenceTest(string $hash)
    {
        $callback = function () use ($hash) {
            $seqs = $this->resource->getReferenceSequences(chroms: ['MT']);
            $this->assertCount(16569, $seqs['MT']->getSequence());
            $this->assertEquals($hash, $seqs['MT']->getMd5());
            $this->assertEquals(1, $seqs['MT']->getStart());
            $this->assertEquals(16569, $seqs['MT']->getEnd());
            $this->assertEquals(16569, $seqs['MT']->getLength());

            $seqs['MT']->clear();
            $reflectionSeqs = new ReflectionClass($seqs['MT']);
            $sequenceProperty = $reflectionSeqs->getProperty('sequence');
            $md5Property = $reflectionSeqs->getProperty('md5');
            $startProperty = $reflectionSeqs->getProperty('start');
            $endProperty = $reflectionSeqs->getProperty('end');
            $lengthProperty = $reflectionSeqs->getProperty('length');

            $sequenceProperty->setAccessible(true);
            $md5Property->setAccessible(true);
            $startProperty->setAccessible(true);
            $endProperty->setAccessible(true);
            $lengthProperty->setAccessible(true);

            // Test the private fields after calling clear method
            $this->assertEquals(0, count($sequenceProperty->getValue($seqs['MT'])));
            $this->assertEquals('', $md5Property->getValue($seqs['MT']));
            $this->assertEquals(0, $startProperty->getValue($seqs['MT']));
            $this->assertEquals(0, $endProperty->getValue($seqs['MT']));
            $this->assertEquals(0, $lengthProperty->getValue($seqs['MT']));


            $this->assertCount(16569, $seqs['MT']->getSequence());
            $this->assertEquals($hash, $seqs['MT']->getMd5());
            $this->assertEquals(1, $seqs['MT']->getStart());
            $this->assertEquals(16569, $seqs['MT']->getEnd());
            $this->assertEquals(16569, $seqs['MT']->getLength());
        };

        $this->runReferenceSequencesTest($callback);
    }


    public function testReferenceSequenceLoadSequence()
    {
        $hash = $this->downloads_enabled
            ? "c68f52674c9fb33aef52dcf399755519"
            : "d432324413a21aa9247321c56c300ad3";

        $this->runReferenceSequenceLoadSequenceTest($hash);
    }

    public function testReferenceSequenceGenericLoadSequence()
    {
        $tmpdir = sys_get_temp_dir();
        $dest = $tmpdir . DIRECTORY_SEPARATOR . "generic.fa.gz";
        gzip_file("tests/input/generic.fa", $dest);

        $seq = new ReferenceSequence("1", $dest);
        $this->assertEquals($seq->getID(), "1");
        $this->assertEquals($seq->getChrom(), "1");
        $this->assertEquals($seq->getPath(), $dest);
        $this->assertEquals(
            $seq->getSequence(),
            new \SplFixedArray([
                "NNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNNAGGCCGGACNNNNNNNN"
            ])
        );
        $this->assertEquals(
            str_split("AGGCCGGAC"),
            array_map('chr', array_slice($seq->getSequence(), 100, 9))
        );
        $this->assertEquals($seq->getMd5(), "6ac6176535ad0e38aba2d05d786c39b6");
        $this->assertEquals($seq->getStart(), 1);
        $this->assertEquals($seq->getEnd(), 117);
        $this->assertEquals($seq->getLength(), 117);
    }

    public function testLoadOpenSnpDatadumpFile()
    {
        $tmpdir = sys_get_temp_dir();
        $this->resource->setResourcesDir($tmpdir);

        // Write test openSNP datadump zip
        $zipPath = $tmpdir . DIRECTORY_SEPARATOR . "opensnp_datadump.current.zip";
        $zip = new \ZipArchive();
        $zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE);
        $zip->addFile("tests/input/generic.csv", "generic1.csv");
        $zip->addFile("tests/input/generic.csv", "generic2.csv");
        $zip->close();

        $snps1 = new SNPs($this->resource->loadOpenSnpDatadumpFile("generic1.csv"));
        $snps2 = new SNPs($this->resource->loadOpenSnpDatadumpFile("generic2.csv"));

        $this->assertDataFrameEquals(
            $snps1->snps,
            $this->genericSnps(),
            true
        );
        $this->assertDataFrameEquals(
            $snps2->snps,
            $this->genericSnps(),
            true
        );

        $this->resource->setResourcesDir("resources");
    }
}
