<?php

namespace Dna\Snps\IO;



use Dna\Snps\SNPsResources;
use League\Csv\Info;
use League\Csv\Reader as CsvReader;
use League\Csv\Statement;
use php_user_filter;
use ZipArchive;

stream_filter_register("extra_tabs_filter", ExtraTabsFilter::class) or die("Failed to register filter");


/**
 * Class for reading and parsing raw data / genotype files.
 */
class Reader
{
    /**
     * Initialize a Reader.
     *
     * @param string $file
     *   Path to file to load or bytes to load.
     * @param bool $_only_detect_source
     *   Flag to indicate if only detecting the source of the data.
     * @param SNPsResources|null $resources
     *   Instance of Resources.
     * @param array $rsids
     *   rsids to extract if loading a VCF file.
     */
    public function __construct(
        private string $file,
        private bool $_only_detect_source,
        private ?SNPsResources $resources,
        private array $rsids
    ) {}

    /**
     * Read and parse a raw data / genotype file.
     *
     * @return array An array with the following items:
     *               - 'snps': Array of parsed SNPs.
     *               - 'source': Detected source of SNPs.
     *               - 'phased': Flag indicating if SNPs are phased.
     *               - 'build': Detected build of SNPs.
     */
    public function read(): array
    {
        $file = $this->file;
        $read_data = [];
        $d = [
            "snps" => [],
            "source" => "",
            "phased" => false,
            "build" => 0,
        ];
        if (is_string($file) && file_exists($file)) {
            if (strpos($file, ".zip") !== false) {
                $zip = new ZipArchive(ZipArchive::RDONLY);
                if ($zip->open($file) === true) {
                    $firstEntry = $zip->getNameIndex(0);
                    $firstEntryFile = $zip->getStream($firstEntry);
                    $read_data = $this->_extract_comments($firstEntryFile, true, true);
                    $compression = "zip";
                    $zip->close();
                }
            } elseif (strpos($file, ".gz") !== false) {
                $fileContents = file_get_contents($file);
                $fileContents = zlib_decode($fileContents);

                $read_data = $this->_extract_comments($fileContents);
                $compression = "gzip";
            } else {
                $fileContents = file_get_contents($file);
                $read_data = $this->_handle_bytes_data($fileContents);
            }
        } elseif (is_string($file)) {
            $fileContents = $file;
            $read_data = $this->_handle_bytes_data($fileContents);

            $tempfile = fopen('php://temp', 'w+');
            fwrite($tempfile, $fileContents);
            $meta_data = stream_get_meta_data($tempfile);
            $file_path = $meta_data['uri'];
            $file = $file_path;
        } else {
            return $d;
        }

        // var_dump($read_data);
        $first_line = $read_data["first_line"] ?? '';
        $comments = $read_data["comments"] ?? '';
        $data = $read_data["data"] ?? '';


        if (strpos($first_line, "23andMe") !== false) {
            // print_r("23andme");
            // Some 23andMe files have separate alleles
            if (str_ends_with(trim($comments), "# rsid\tchromosome\tposition\tallele1\tallele2")) {
                $d = $this->read_23andme($file, $compression, false);
            }
            // Some 23andMe files have a combined genotype
            elseif (str_ends_with(trim($comments), "# rsid\tchromosome\tposition\tgenotype")) {
                $d = $this->read_23andme($file, $compression, true);
            }
            // Something we haven't seen before and can't handle
            else {
                return $d;
            }
        } else if (strpos($first_line, 'AncestryDNA') !== false) {
            // print_r("AncestryDNA");
            $d = $this->read_ancestry($file, $compression);
        } else if (str_starts_with($first_line, "[Header]")) {
            // print_r("GSA");
            // Global Screening Array, includes SANO and CODIGO46
            $d = $this->readGsa($file, $compression, $comments);
        } else if (preg_match("/^#*[ \t]*rsid[, \t]*chr/", $first_line)) {
            // print_r("generic");
            $d = $this->readGeneric($file, $compression);
        } else if (preg_match("/rs[0-9]*[, \t]{1}[1]/", $first_line)) {
            // print_r("generic");
            $d = $this->readGeneric($file, $compression, 0);
        }


        // Detect build from comments if build was not already detected from `read` method
        if (!$d["build"]) {
            $d["build"] = $this->_detect_build_from_comments($comments, $d["source"]);
        }


        return $d;
    }


    private function _extract_comments($f, $decode = false, $include_data = false)
    {
        $line = $this->readLine($f, $decode);

        $first_line = $line;
        $comments = "";
        $data = "";

        // echo "Read line\n";
        // print_r($line);
        // var_dump(strpos($first_line, "[Header]"));

        if (strpos($first_line, "#") === 0) {
            while (strpos($line, "#") === 0) {
                $comments .= $line;
                $line = $this->readLine($f, $decode);
            }
            if ($include_data) {
                if (!$data) {
                    $data = stream_get_contents($f);
                    if ($decode) {
                        $data = mb_convert_encoding($data, "UTF-8", mb_detect_encoding($data));
                    }
                }
            }
        } elseif (strpos($first_line, "[Header]") === 0) {
            while (strpos($line, "[Data]") !== 0) {
                $comments .= $line;
                $line = $this->readLine($f, $decode);
            }
            // Ignore the [Data] row
            $line = $this->readLine($f, $decode);
            if ($include_data) {
                while ($line) {
                    $data .= $line;
                    $line = $this->readLine($f, $decode);
                }
            }
        }

        if (!$data && $include_data) {
            $data = stream_get_contents($f);
            if ($decode) {
                $data = mb_convert_encoding($data, "UTF-8", mb_detect_encoding($data));
            }
        }

        if (!$f instanceof ZipArchive) {
            fseek($f, 0);
        }


        return compact('first_line', 'comments', 'data');
    }

    /**
     * Handle the bytes data file and extract comments based on the data format.
     *
     * @param string $file The bytes data file.
     * @param bool $include_data Whether to include the data.
     * @return array Returns an array containing the first line, comments, data, and compression.
     */
    private function _handle_bytes_data($file, $include_data = false)
    {
        $compression = "infer";
        $data = [];
        if ($this->is_zip($file)) {
            echo "zip\n";
            var_dump($file);
            $compression = "zip";
            $z = new ZipArchive();
            $z->open("data.zip");
            $namelist = array();
            for ($i = 0; $i < $z->numFiles; $i++) {
                $namelist[] = $z->getNameIndex($i);
            }
            $key = "GFG_filtered_unphased_genotypes_23andMe.txt";
            $key_search = array_map(function ($name) use ($key) {
                return strpos($name, $key) !== false;
            }, $namelist);

            if (in_array(true, $key_search)) {
                $filename = $namelist[array_search(true, $key_search)];
            } else {
                $filename = $namelist[0];
            }
            $fileContents = $z->getFromName($filename);
            $z->close();
            $data = $this->_extract_comments($fileContents, true, $include_data);
        } elseif ($this->is_gzip($file)) {
            $compression = "gzip";
            $fileContents = gzdecode($file);
            $data = $this->_extract_comments($fileContents, true, $include_data);
        } else {
            $compression = null;
            $fileHandle = fopen('php://memory', 'r+');
            fwrite($fileHandle, $file);
            rewind($fileHandle);
            $data = $this->_extract_comments($fileHandle, true, $include_data);
            fseek($fileHandle, 0);
            fclose($fileHandle);
        }

        return array_merge($data, [$compression]);
    }

    /**
     * Generic method to help read files.
     *
     * @param string $source The name of the data source.
     * @param callable $parser The parsing function, which returns a tuple with the following items:
     *                         0. array The parsed SNPs (empty if only detecting source).
     *                         1. bool|null Optional. Flag indicating if SNPs are phased.
     *                         2. int|null Optional. Detected build of SNPs.
     * @return array Returns an array with the following items:
     *               'snps' (array) The parsed SNPs.
     *               'source' (string) The detected source of SNPs.
     *               'phased' (bool) Flag indicating if SNPs are phased.
     *               'build' (int) The detected build of SNPs.
     */
    private function readHelper($source, $parser)
    {
        $phased = false;
        $build = 0;
        $snps = [];

        if ($this->_only_detect_source) {
            $snps = array();
        } else {
            $result = $parser();
            $snps = $result[0];
            $phased = $result[1] ?? false;
            $build = $result[2] ?? 0;
        }

        return array(
            'snps' => $snps,
            'source' => $source,
            'phased' => $phased,
            'build' => $build
        );
    }


    private function _detect_build_from_comments($comments, $source)
    {
        // If it's a VCF, parse it properly
        if ($source === "vcf") {
            $lines = explode("\n", $comments);
            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === "") {
                    // Skip blanks
                    continue;
                }
                if (!str_starts_with($line, "##")) {
                    // Skip comments but not preamble
                    continue;
                }
                if (strpos($line, "=") === false) {
                    // Skip lines without key/value
                    continue;
                }
                $line = substr($line, 2);
                list($key, $value) = explode("=", $line, 2);
                $key = trim($key);
                $value = trim($value);
                if (strtolower($key) === "contig") {
                    $parts = explode(",", substr($value, 1, -1));
                    foreach ($parts as $part) {
                        list($part_key, $part_value) = explode("=", $part, 2);
                        $part_key = trim($part_key);
                        $part_value = trim($part_value);
                        if (strtolower($part_key) === "assembly") {
                            if (strpos($part_value, "36") !== false) {
                                return 36;
                            } elseif (strpos($part_value, "37") !== false || strpos($part_value, "hg19") !== false) {
                                return 37;
                            } elseif (strpos($part_value, "38") !== false) {
                                return 38;
                            }
                        } elseif (strtolower($part_key) === "length") {
                            if ($part_value === "249250621") {
                                return 37; // Length of chromosome 1
                            } elseif ($part_value === "248956422") {
                                return 38; // Length of chromosome 1
                            }
                        }
                    }
                }
            }
            // Couldn't find anything
            return 0;
        } else {
            // Not a VCF
            $comments = strtolower($comments);
            if (str_starts_with($comments, "build 36")) {
                return 36;
            } elseif (str_starts_with($comments, "build 37")) {
                return 37;
            } elseif (str_starts_with($comments, "build 38")) {
                return 38;
            } elseif (str_starts_with($comments, "grch38")) {
                return 38;
            } elseif (str_starts_with($comments, "grch37")) {
                return 37;
            } elseif (str_starts_with($comments, "249250621")) {
                return 37; // Length of chromosome 1
            } elseif (str_starts_with($comments, "248956422")) {
                return 38; // Length of chromosome 1
            } else {
                return 0;
            }
        }
    }


    /**
     * Check whether or not a bytes_data file is a valid Zip file.
     *
     * @param string $bytes_data The bytes data to check.
     * @return bool Returns true if the bytes data is a valid Zip file, false otherwise.
     */
    public static function is_zip($bytes_data)
    {
        if (empty($bytes_data)) return false;
        $file = tmpfile();
        fwrite($file, $bytes_data);
        $meta_data = stream_get_meta_data($file);
        $file_path = $meta_data['uri'];

        $is_zip = false;
        if (class_exists('ZipArchive')) {
            $zip = new ZipArchive();
            if ($zip->open($file_path) === true) {
                $is_zip = true;
                $zip->close();
            }
        }

        fclose($file);
        return $is_zip;
    }

    /**
     * Check whether or not a bytes_data file is a valid gzip file.
     *
     * @param string $bytes_data The bytes data to check.
     * @return bool Returns true if the bytes data is a valid gzip file, false otherwise.
     */
    public static function is_gzip($bytes_data)
    {
        $is_gzip = false;
        if (substr(bin2hex($bytes_data), 0, 4) === "1f8b") {
            $is_gzip = true;
        }
        return $is_gzip;
    }


    /**
     * Read a line from the file.
     *
     * @param resource $f
     *   The file resource.
     * @param bool $decode
     *   Flag indicating if the line should be decoded as UTF-8.
     *
     * @return string
     *   The read line.
     */
    private static function readLine($f, bool $decode): string
    {
        if ($decode) {
            // https://stackoverflow.com/a/606199
            $data = fgets($f);
            return mb_convert_encoding($data, "UTF-8", mb_detect_encoding($data));;
        } else {
            return fgets($f);
        }
    }

    /**
     * Read and parse 23andMe file.
     *
     * @param string $file The path to the file.
     * @param string|null $compression The compression type (e.g., "zip", "gzip"). Defaults to null.
     * @param bool $joined Indicates whether the file has joined columns. Defaults to true.
     * @return array Returns the result of `readHelper`.
     */
    private function read_23andme($file, $compression = null, $joined = true)
    {
        $mapping = array(
            "1" => "1",
            "2" => "2",
            "3" => "3",
            "4" => "4",
            "5" => "5",
            "6" => "6",
            "7" => "7",
            "8" => "8",
            "9" => "9",
            "10" => "10",
            "11" => "11",
            "12" => "12",
            "13" => "13",
            "14" => "14",
            "15" => "15",
            "16" => "16",
            "17" => "17",
            "18" => "18",
            "19" => "19",
            "20" => "20",
            "21" => "21",
            "22" => "22",
            1 => "1",
            2 => "2",
            3 => "3",
            4 => "4",
            5 => "5",
            6 => "6",
            7 => "7",
            8 => "8",
            9 => "9",
            10 => "10",
            11 => "11",
            12 => "12",
            13 => "13",
            14 => "14",
            15 => "15",
            16 => "16",
            17 => "17",
            18 => "18",
            19 => "19",
            20 => "20",
            21 => "21",
            22 => "22",
            "X" => "X",
            "Y" => "Y",
            "MT" => "MT"
        );

        $parser = function () use ($file, $joined, $compression, $mapping) {
            if ($joined) {
                $columnnames = ["rsid", "chrom", "pos", "genotype"];
            } else {
                $columnnames = ["rsid", "chrom", "pos", "allele1", "allele2"];
            }

            $lines = file($file);
            $df = [];

            foreach ($lines as $line) {
                $line = trim($line);
                if ($line === "" || $line[0] === "#") {
                    continue;
                }
                $data = explode("\t", $line);
                $row = array_combine($columnnames, $data);
                if (!$joined) {
                    $row["genotype"] = $row["allele1"] . $row["allele2"];
                    unset($row["allele1"], $row["allele2"]);
                }
                $df[] = $row;
            }

            $df = array_map(function ($row) use ($mapping) {
                if (isset($row["chrom"]) && isset($mapping[$row["chrom"]])) {
                    $row["chrom"] = $mapping[$row["chrom"]];
                }
                return $row;
            }, $df);

            $na_values = ['--', '-'];
            foreach ($df as $index => $row) {
                foreach ($row as $key => $value) {
                    if (in_array($value, $na_values)) {
                        $df[$index][$key] = null;
                    }
                }
            }

            $df = array_filter($df, function ($row) {
                return isset($row["rsid"]) && isset($row["chrom"]) && isset($row["pos"]);
            });

            return [$df];
        };

        return $this->readHelper("23andMe", $parser);
    }


    /**
     * Reads and parses an Ancestry.com file.
     *
     * @param string $file Path to file
     * @return array Result of `readHelper`
     */
    public function read_ancestry($file)
    {

        $parser = function () use ($file) {
            $data = [];
            $csv = CsvReader::createFromPath($file, 'r');
            if ($csv->supportsStreamFilterOnRead())
                $csv->addStreamFilter('extra_tabs_filter');
            $csv->setDelimiter("\t");

            $headerRowIndex = $this->findHeaderRow($csv);
            $csv->setHeaderOffset($headerRowIndex);

            foreach ($csv->getRecords() as $record) {
                // print_r($record);
                if (isset($record['rsid']) && strpos($record['rsid'], '#') === 0) {
                    continue;
                }

                // print_r($record);

                $na_values = [0];
                foreach ($record as $key => $value) {
                    if (in_array($value, $na_values)) {
                        $record[$key] = null;
                    }
                }

                // Create an array with the rsid, chrom, pos, and genotype
                $entry = [
                    'rsid' => $record['rsid'],
                    'chrom' => $record['chromosome'],
                    'pos' => $record['position'],
                    'genotype' => $record['allele1'] . $record['allele2']
                ];


                if (empty($entry['genotype'])) {
                    $entry['genotype'] = null;
                }

                // Adjust chrom values
                switch ($entry['chrom']) {
                    case '23':
                        $entry['chrom'] = 'X';
                        break;
                    case '24':
                        $entry['chrom'] = 'Y';
                        break;
                    case '25':
                        $entry['chrom'] = 'PAR';
                        break;
                    case '26':
                        $entry['chrom'] = 'MT';
                        break;
                }

                $data[] = $entry;
            }

            // print_r(count($data));

            return [$data];
        };

        return $this->readHelper('AncestryDNA', $parser);
    }

    /**
     * Finds the index of header row in a CSV file.
     *
     * @param Reader $csv CSV reader
     * @return int Index of header row
     */
    private function findHeaderRow($csv)
    {
        foreach ($csv as $index => $record) {
            if (strpos($record[0], '#') !== 0) {
                return $index;
            }
        }
        return 0;
    }

    /**
     * Read and parse Illumina Global Screening Array files
     *
     * @param string $dataOrFilename Either the filename to read from or the bytes data itself.
     * @param string $compression Compression type
     * @param string $comments Comments related to the data
     * 
     * @return array Result of `readHelper`
     */
    public function readGsa($dataOrFilename, $compression, $comments)
    {
        // Pick the source
        // Ideally we want something more specific than GSA
        if (strpos($comments, 'SANO') !== false) {
            $source = 'Sano';
        } elseif (strpos($comments, 'CODIGO46') !== false) {
            $source = 'Codigo46';
        } else {
            // Default to generic global screening array
            $source = 'GSA';
        }

        return $this->_readGsaHelper($dataOrFilename, $source);
    }

    protected function _readGsaHelper($file, $source)
    {
        $parser = function () use ($file, $source) {
            // Read the comments so we get to the actual data
            if (is_string($file)) {
                try {
                    $handle = fopen($file, "rb");
                    if ($handle === false) {
                        throw new \RuntimeException("Failed to open file: $file");
                    }
                    $extracted = $this->_extract_comments($handle, true, true);
                    $data = $extracted['data'];
                } catch (\InvalidArgumentException $e) {
                    // compressed file on filesystem
                    $handle = fopen($file, "rb");
                    if ($handle === false) {
                        throw new \RuntimeException("Failed to open file: $file");
                    }
                    $data = stream_get_contents($handle);
                    if ($data === false) {
                        throw new \RuntimeException("Failed to read from file: $file");
                    }
                    $bytes_data = $this->_handle_bytes_data($data, true);
                    $data = $bytes_data['data'];
                } finally {
                    if (isset($handle) && is_resource($handle)) {
                        fclose($handle);
                    }
                }
            } else {
                $data = stream_get_contents($file);
                $bytes_data = $this->_handle_bytes_data($data, true);
                $data = $bytes_data['data'];
            }

            // Read the data into an array for manipulation
            $csv = CsvReader::createFromString($data);
            $csv->setDelimiter("\t");
            $records = $csv->getRecords();

            $dataArr = [];
            $firstrsid = null;
            foreach ($records as $offset => $record) {
                if ($offset == 0) $firstrsid = $record["rsid"];
                $dataArr[] = $record;
            }

            // Ensure that 'rsid', 'chrom', 'pos' and 'genotype' are not in the column names
            if (
                !is_null($firstrsid)
                && (!in_array('rsid', $dataArr[$firstrsid]) && !in_array('chrom', $dataArr[$firstrsid])
                    && !in_array('pos', $dataArr[$firstrsid]) && !in_array('genotype', $dataArr[$firstrsid])
                )
            ) {

                // Prefer the specified chromosome and position, if present
                if (in_array('Chr', $dataArr[$firstrsid]) && in_array('Position', $dataArr[$firstrsid])) {
                    // Put the chromosome in the right column with the right type
                    $dataArr = array_map(function ($row) {
                        $row['chrom'] = (string)$row['Chr'];
                        $row['pos'] = (int)$row['Position'];
                        return $row;
                    }, $dataArr);
                } else {
                    // Use an external source to map SNP names to chromosome and position
                    // Assume get_gsa_chrpos returns array with 'gsaname_chrpos', 'gsachr', 'gsapos'
                    $gsaChrPos = $this->resources->getGsaChrpos();

                    // Merge the two arrays
                    foreach ($dataArr as $key => $value) {
                        $posKey = array_search($value['SNP Name'], array_column($gsaChrPos, 'gsaname_chrpos'));
                        if ($posKey !== false) {
                            $dataArr[$key] = array_merge($value, $gsaChrPos[$posKey]);
                            $dataArr[$key]['chrom'] = (string)$dataArr[$key]['gsachr'];
                            $dataArr[$key]['pos'] = (int)$dataArr[$key]['gsapos'];
                        }
                    }
                }
            }

            // Use the given rsid when available, SNP Name when unavailable
            foreach ($dataArr as $key => $value) {
                $dataArr[$key]['rsid'] = (string)$value['SNP Name'];
                if (isset($value['RsID']) && $value['RsID'] != '.') {
                    $dataArr[$key]['rsid'] = $value['RsID'];
                } else {
                    // If given RSIDs are not available, then use the external mapping to turn
                    // SNP names into rsids where possible
                    // Assume get_gsa_rsid returns array with 'gsaname_rsid', 'gsarsid'
                    $gsaRsid = $this->resources->getGsaRsid();

                    // Merge the two arrays
                    foreach ($dataArr as $k => $v) {
                        $posKey = array_search($v['SNP Name'], array_column($gsaRsid, 'gsaname_rsid'));
                        if ($posKey !== false) {
                            $dataArr[$k] = array_merge($v, $gsaRsid[$posKey]);
                            $dataArr[$k]['rsid'] = (string)$dataArr[$k]['gsarsid'];
                        }
                    }
                }

                // Combine the alleles into genotype
                // Prefer Plus strand as that is forward reference
                if (isset($value['Allele1 - Plus']) && isset($value['Allele2 - Plus'])) {
                    $dataArr[$key]['genotype'] = (string)($value['Allele1 - Plus'] . $value['Allele2 - Plus']);
                } elseif (isset($value['Allele1 - Forward']) && isset($value['Allele2 - Forward'])) {
                    // If strand is forward, need to take reverse complement of *some* rsids
                    // This is because it is Illumina forward, which is dbSNP strand, which
                    // is reverse reference for some RSIDs before dbSNP 151.

                    // Load list of reversible rsids
                    // Assume get_dbsnp_151_37_reverse returns array with 'dbsnp151revrsid'
                    $dbsnp151 = $this->resources->get_dbsnp_151_37_reverse();
                    $dbsnp151 = array_column($dbsnp151, 'dbsnp151revrsid');

                    // Add it as an extra column
                    foreach ($dataArr as $k => $v) {
                        $posKey = array_search($v['rsid'], $dbsnp151);
                        if ($posKey !== false) {
                            $dataArr[$k]['dbsnp151revrsid'] = $dbsnp151[$posKey];
                        }
                    }
                }

                // Create plus strand columns from the forward alleles and flip them if appropriate
                for ($i = 1; $i <= 2; $i++) {
                    foreach ($dataArr as $key => $value) {
                        $dataArr[$key]["Allele{$i} - Plus"] = $value["Allele{$i} - Forward"];

                        if ($value["Allele{$i} - Forward"] == "A" && isset($value["dbsnp151revrsid"])) {
                            $dataArr[$key]["Allele{$i} - Plus"] = "T";
                        }
                        if ($value["Allele{$i} - Forward"] == "T" && isset($value["dbsnp151revrsid"])) {
                            $dataArr[$key]["Allele{$i} - Plus"] = "A";
                        }
                        if ($value["Allele{$i} - Forward"] == "C" && isset($value["dbsnp151revrsid"])) {
                            $dataArr[$key]["Allele{$i} - Plus"] = "G";
                        }
                        if ($value["Allele{$i} - Forward"] == "G" && isset($value["dbsnp151revrsid"])) {
                            $dataArr[$key]["Allele{$i} - Plus"] = "C";
                        }

                        // Create a genotype by combining the new plus columns
                        $dataArr[$key]['genotype'] = (string)($value['Allele1 - Plus'] . $value['Allele2 - Plus']);
                    }
                }

                // Mark -- genotype as na
                foreach ($dataArr as $key => $value) {
                    if ($value['genotype'] == '--') {
                        $dataArr[$key]['genotype'] = null;
                    }
                }

                // Keep only the columns we want
                $dataArr = array_map(function ($item) {
                    return [
                        'rsid' => $item['rsid'],
                        'chrom' => $item['chrom'],
                        'pos' => $item['pos'],
                        'genotype' => $item['genotype']
                    ];
                }, $dataArr);

                // Discard rows without values
                $dataArr = array_filter($dataArr, function ($item) {
                    return !is_null($item['rsid']) && !is_null($item['chrom']) && !is_null($item['pos']);
                });

                // ... Code to reindex for new identifiers, if needed...
                // df.set_index(["rsid"], inplace=True)
                return $dataArr;
            }
        };

        echo "Read helper\n";
        echo $source;
        return $this->readHelper($source, $parser);
    }

    /**
     * Read and parse a generic CSV or TSV file.
     *
     * Assumes columns are 'rsid', 'chrom' / 'chromosome', 'pos' / 'position', and 'genotype';
     * values are comma separated; unreported genotypes are indicated by '--'; and one header row
     * precedes data.
     *
     * @param string $file Path to file
     * @param string|null $compression Compression type
     * @param int $skip Number of rows to skip
     * @return array Result of `readHelper`
     */
    public function readGeneric(string $file, ?string $compression, int $skip = 1): array
    {
        $parser = function () use ($file, $compression, $skip) {
            $parse = function ($sep, $use_cols = false) use ($file, $skip, $compression) {
                // Stream filter for compressed file, if needed
                $filter = $compression === "gzip" ? 'compress.zlib://' : '';

             
                // CSV Reader setup
                $csv = CsvReader::createFromPath($filter . $file, 'r');

                $stats = Info::getDelimiterStats($csv, [",", "\t"], 10);
     
                $sep = array_flip($stats)[max($stats)];
                $csv->setDelimiter($sep);
                if ($skip > 0)
                    $csv->setHeaderOffset($skip - 1);


                $results = [];
                $stmt = Statement::create();
                // ->offset($skip);

                
                $records = $stmt->process($csv);
                

                if (!$use_cols)
                    $records = $stmt->process($csv, ["rsid", "chrom", "pos", "genotype"]);

                foreach ($records as $record) {
                    // Convert '--' to NULL
                    $record = array_map(function ($value) {
                        return $value == '--' ? null : $value;
                    }, $record);

                    if ($use_cols) {                    
                        $record = [
                            "rsid" => $record[0],
                            "chrom" => $record[1],
                            "pos" => $record[2],
                            "genotype" => $record[3],
                        ];
                    }

                    $key = explode(",", $record["rsid"], 2)[0];
                    $record["rsid"] = $key;
                    $results[] = $record;
                }

                return $results;
            };

         
                try {
                    return [$parse('\t')];
                } catch (\Exception $e) {
                    return [$parse("\t", true)];
                }
            
        };

        return $this->readHelper('generic', $parser);
    }
}
