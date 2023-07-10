<?php

namespace Dna\Snps\IO;

use Dna\Snps\SNPsResources;
use ZipArchive;

use function RectorPrefix20211020\print_node;

function get_empty_snps_dataframe()
{
    $columns = array("rsid", "chrom", "pos", "genotype");
    $df = array();
    $df[] = $columns;
    return $df;
}

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
        private string $file = '',
        private bool $_only_detect_source = false,
        private ?SNPsResources $resources = null,
        private array $rsids = []
    ) {
    }

    /**
     * Read and parse a raw data / genotype file.
     *
     * @return array
     *   An array with the following items:
     *   - snps (pandas.DataFrame): Dataframe of parsed SNPs.
     *   - source (string): Detected source of SNPs.
     *   - phased (bool): Flag indicating if SNPs are phased.
     */
    public function read(): array
    {
        $file = $this->file;
        $compression = "infer";
        $read_data = [];
        $d = array(
            "snps" => get_empty_snps_dataframe(),
            "source" => "",
            "phased" => false,
            "build" => 0,
        );

        // Peek into files to determine the data format
        if (is_string($file) && file_exists($file)) {
            echo "file exists\n";  
            if (strpos($file, ".zip") !== false) {
                $zip = new ZipArchive();
                if ($zip->open($file) === true) {
                    $firstEntry = $zip->getNameIndex(0);
                    $firstEntryFile = $zip->getStream($firstEntry);
                    $first_line = '';
                    $comments = '';
                    $data = '';
                    $this->_extract_comments($firstEntryFile, true, $first_line, $comments, $data);
                    $compression = "zip";
                    $zip->close();
                }
            } elseif (strpos($file, ".gz") !== false) {
                $fileContents = file_get_contents($file);
                $fileContents = gzdecode($fileContents);
               
                $read_data = $this->_extract_comments($fileContents);
                $compression = "gzip";
            } else {
                $fileContents = file_get_contents($file);               
                $read_data = $this->_handle_bytes_data($fileContents);
            }
        } elseif (is_string($file)) {
            $fileContents = $file;            
            $read_data = $this->_handle_bytes_data($fileContents);
        } else {
            return $d;
        }
        
        $first_line = $read_data["first_line"] ?? '';
        $comments = $read_data["comments"] ?? '';
        $data = $read_data["data"] ?? '';
        echo "first_line: $first_line\n";
        print_r($read_data);

        if (strpos($first_line, "23andMe") !== false) {
            echo "its a 23andMe\n";
            // Some 23andMe files have separate alleles
            if (str_ends_with(trim($comments), "# rsid\tchromosome\tposition\tallele1\tallele2")) {
                echo "Option 1\n";
                $d = $this->read_23andme($file, $compression, false);
            }
            // Some 23andMe files have a combined genotype
            elseif (str_ends_with(trim($comments), "# rsid\tchromosome\tposition\tgenotype" )) {
                echo "Option 2\n";
                $d = $this->read_23andme($file, $compression, true);
                var_dump($d);
            }
            // Something we haven't seen before and can't handle
            else {
                echo "Option 3\n";
                return $d;
            }
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
            while (!strpos($line, "[Data]") === 0) {
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
    private function read_helper($source, $parser)
    {
        $phased = false;
        $build = 0;

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
     * @return array Returns the result of `read_helper`.
     */
    private function read_23andme($file, $compression = null, $joined = true)
    {
        echo "READ 23";
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
            echo "Inna d parser";
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

            $df = array_filter($df, function ($row) {
                return isset($row["rsid"]) && isset($row["chrom"]) && isset($row["pos"]);
            });
    
            return [$df];
        };

        $res = $this->read_helper("23andMe", $parser);
        echo "res:";
        var_dump($res);
        return $res;

    }
}
