<?php

/**
 * php-dna.
 *
 * tools for genetic genealogy and the analysis of consumer DNA test results
 *
 * @author          Liberu Software Ltd <support@laravel-liberu.com>
 * @copyright       Copyright (c) 2020-2023, Liberu Software Ltd
 * @license         MIT
 *
 * @link            http://github.com/laravel-liberu/php-dna
 */

namespace Dna;

use Exception;

/**
 * Class Resources.
 */
class Resources extends \Dna\Snps\SNPsResources {

    protected $_genetic_map = '{}';
    protected $_genetic_map_name = '';
    protected $_cytoBand_hg19 = [];
    protected $_knownGene_hg19 = [];
    protected $_kgXref_hg19 = [];

    public function __construct($resources_dir = 'resources')
    {
        parent::__construct($resources_dir = $resources_dir);
    }


    // {  
    //     // Check if the current genetic map is already HapMap2
    //     if ($this->_genetic_map_name !== "HapMap2") {
    //         // If not already HapMap2, load the HapMap2 genetic map and set it as the current genetic map
    //         $this->_genetic_map = $this->_load_genetic_map_HapMapII_GRCh37(
    //             $this->_get_path_genetic_map_HapMapII_GRCh37()
    //         );
    //         $this->_genetic_map_name = "HapMap2";
    //     }

    //     // Return the HapMap2 genetic map in GRCh37 format
    //     return $this->_genetic_map;
    // }

    // /**
    //  * Returns the genetic map for a given population in the 1000 Genomes Project GRCh37 reference genome.
    //  *
    //  * @param string $pop The population code (e.g. "CEU", "YRI", "CHB") for which to retrieve the genetic map.
    //  * @return array The genetic map for the specified population.
    //  */
    // public function get_genetic_map_1000G_GRCh37(string $pop): array
    // {
    //     // Check if the requested genetic map is already loaded
    //     if ($this->_genetic_map_name !== $pop) {
    //         // If not, load the genetic map from file
    //         $this->_genetic_map = $this->_load_genetic_map_1000G_GRCh37(
    //             $this->_get_path_genetic_map_1000G_GRCh37(pop: $pop)
    //         );
    //         // Update the name of the loaded genetic map
    //         $this->_genetic_map_name = $pop;
    //     }

    //     // Return the loaded genetic map
    //     return $this->_genetic_map;
    // }

    /**
     * Returns the cytogenetic banding information for the hg19 reference genome.
     *
     * @return array The cytogenetic banding information for hg19.
     */
    public function getCytoBandHg19(): array
    {
        // Check if the cytogenetic banding information for hg19 is already loaded
        if (empty($this->_cytoBand_hg19)) {
            // If not, load the cytogenetic banding information from file
            $this->_cytoBand_hg19 = $this->loadCytoBand($this->getPathCytoBandHg19());
        }

        return $this->_cytoBand_hg19;
    }
    
    // /**
    //  * Returns the knownGene_hg19 data.
    //  *
    //  * @return array The knownGene_hg19 data.
    //  */
    // public function get_knownGene_hg19() {
    //     // Check if the _knownGene_hg19 property is empty.
    //     if ($this->_knownGene_hg19->empty()) {
    //         // If it is empty, load the knownGene_hg19 data from the file path.
    //         $this->_knownGene_hg19 = $this->_load_knownGene(
    //             $this->_get_path_knownGene_hg19()
    //         );
    //     }
    //     // Return the knownGene_hg19 data.
    //     return $this->_knownGene_hg19;
    // }

    /**
     * Returns the kgXref data for the hg19 reference genome.
     *
     * @return array The kgXref data for hg19.
     */
    public function getKgXrefHg19(): array
    {
        // Check if the _kgXref_hg19 property is empty.
        if (empty($this->_kgXref_hg19)) {
            // If it is empty, load the kgXref_hg19 data from the file path.
            $this->_kgXref_hg19 = $this->loadKgXref(
                $this->getPathKgXrefHg19()
            );
        }

        return $this->_kgXref_hg19;
    }

    // public function _load_genetic_map_HapMapII_GRCh37($filename) 
    // {
    //     $genetic_map = array(  );
    //     $archive = new PharData($filename);
    //     foreach($archive as $file) {
    //         if (strpos("genetic_map",$file["name"])===true){
    //             $df = array(  );
    //             if (($handle = fopen($file, "r")) !== FALSE) {
    //                 while (($data = fgetcsv($handle,"\t")) !== FALSE) {
    //                     $df["Position(bp)"]=$data["pos"];
    //                     $df["Rate(cM/Mb)"]=$data["rate"];
    //                     $df["Map(cM)"]=$data["map"];
    //                 }
    //                 fclose($handle);
    //             }
    //             $start_pos = strpos($file["name"],"chr") + 3;
    //             $end_pos = strpos($file["name"],".");
    //             $genetic_map[substr($file["name"],$start_pos,$end_pos)] = $df;
    //         }
    //     }
    //     $genetic_map["X"] = array_merge(
    //         $genetic_map["X_par1"], $genetic_map["X"], $genetic_map["X_par2"]
    //     );
    //     $genetic_map["X_par1"]=array( );
    //     $genetic_map["X_par2"]=array( );
    //     return $genetic_map;
    // }

    /**
     * Loads a genetic map from a file in the 1000 Genomes Project format (GRCh37).
     *
     * @param string $filename The path to the file to load.
     * @return array An associative array of genetic maps, keyed by chromosome.
     */
    function loadGeneticMap1000GGRCh37($filename) 
    {
        $geneticMap = []; // Initialize an empty array to hold the genetic maps.

        $phar = new PharData($filename); // Create a new PharData object from the file.

        foreach ($phar as $member) { // Loop through each file in the Phar archive.
            $filepath = $member->getPathname(); // Get the path to the file.
            $geneticMap = $this->processGeneticMapFile($filepath, $geneticMap);
        }

        return $geneticMap; // Return the $geneticMap array.
    }

    /**
     * Processes a single genetic map file and adds the data to the $geneticMap array.
     *
     * @param string $filepath The path to the genetic map file.
     * @param array $geneticMap The array to add the genetic map data to.
     * @return array The updated $geneticMap array.
     */
    function processGeneticMapFile($filepath, $geneticMap)
    {
        $file = gzopen($filepath, 'r'); // Open the file for reading.
        $header = fgetcsv($file, 0, "\t"); // Read the header row of the CSV file.

        $tempFile = []; // Initialize an empty array to hold the data rows.
        while (($data = fgetcsv($file, 0, "\t")) !== false) { // Loop through each row of the CSV file.
            if (count($data) == count($header)) { // Check that the row has the same number of columns as the header.
                $tempFile[] = array_combine($header, $data); // Combine the header and data rows into an associative array.
            }
        }

        $df = []; // Initialize an empty array to hold the genetic map data.
        foreach ($tempFile as $row) { // Loop through each row of the data.
            $df[] = [ // Add a new array to the $df array.
                "pos" => $row["Position(bp)"], // Add the position to the array.
                "rate" => $row["Rate(cM/Mb)"], // Add the rate to the array.
                "map" => $row["Map(cM)"], // Add the map to the array.
            ];
        }

        $chrom = explode("-", basename($filepath))[1]; // Get the chromosome number from the filename.
        $geneticMap[$chrom] = $df; // Add the genetic map data to the $geneticMap array, keyed by chromosome.

        return $geneticMap;
    }

    // // public function downloadFile($url, $filename, $compress=False, $timeout=30) 
    // // {
    // //     if(strpos($url, "ftp://") !== false) {
    // //     $url=str_replace($url,"ftp://", "http://");
    // //     } 
    // //     if ($compress && substr($filename,strlen($filename)-3,strlen($filename)) != ".gz"){
    // //     $filename = $filename+".gz";
    // //     }
    // //     $destination = join($this->resources_dir, $filename);

    // //     if (!mkdir($destination)){
    // //     return "";
    // //     }
    // //     if (file_exists($destination)) {            
    // //     $file_url = $destination;
    // //     header('Content-Type: application/octet-stream');
    // //     header('Content-Description: File Transfer');
    // //     header('Content-Disposition: attachment; filename=' . $filename);
    // //     header('Expires: 0');
    // //     header('Cache-Control: must-revalidate');
    // //     header('Pragma: public');
    // //     header('Content-Length: ' . filesize($file_url));
    // //     readfile($file_url);

    // //     // if $compress
    // //     //     $this->_write_data_to_gzip(f, data)
    // //     // else
    // //     //     f.write(data)           
    // //     }   
    // //     return $destination;
    // // }
    
    // /**
    //  * Load UCSC knownGene table.
    //  *
    //  * @param string $filename Path to knownGene file
    //  *
    //  * @return array KnownGene table (associative array)
    //  */
    // public static function loadKnownGene(string $filename): array
    // {
    //     $file = fopen($filename, 'r');
    //     $headers = [
    //         'name',
    //         'chrom',
    //         'strand',
    //         'txStart',
    //         'txEnd',
    //         'cdsStart',
    //         'cdsEnd',
    //         'exonCount',
    //         'exonStarts',
    //         'exonEnds',
    //         'proteinID',
    //         'alignID',
    //     ];
    //     $knownGene = [];

    //     while (($row = fgetcsv($file, 0, "\t")) !== false) {
    //         $rowData = array_combine($headers, $row);
    //         $rowData['chrom'] = substr($rowData['chrom'], 3);
    //         $knownGene[$rowData['name']] = $rowData;
    //     }

    //     fclose($file);

    //     return $knownGene;
    // }

    // /**
    //  * Load UCSC kgXref table.
    //  *
    //  * @param string $filename Path to kgXref file
    //  *
    //  * @return array kgXref table (associative array)
    //  */
    // public static function loadKgXref(string $filename): array
    // {
    //     $file = fopen($filename, 'r');
    //     $headers = [
    //         'kgID',
    //         'mRNA',
    //         'spID',
    //         'spDisplayID',
    //         'geneSymbol',
    //         'refseq',
    //         'protAcc',
    //         'description',
    //         'rfamAcc',
    //         'tRnaName',
    //     ];
    //     $kgXref = [];

    //     while (($row = fgetcsv($file, 0, "\t")) !== false) {
    //         $rowData = array_combine($headers, $row);
    //         $kgXref[$rowData['kgID']] = $rowData;
    //     }

    //     fclose($file);

    //     return $kgXref;
    // }

    /**
     * Get local path to cytoBand file for hg19 / GRCh37 from UCSC, downloading if necessary.
     *
     * @return string Path to cytoBand_hg19.txt.gz
     */
    public function getPathCytoBandHg19(): string
    {
        return $this->downloadFile(
            'ftp://hgdownload.cse.ucsc.edu/goldenPath/hg19/database/cytoBand.txt.gz',
            'cytoBand_hg19.txt.gz'
        );
    }

    // /**
    //  * Download file from a given URL if not exists and return its local path.
    //  *
    //  * @param string $url URL of the file to download
    //  * @param string $filename Local name for the downloaded file
    //  *
    //  * @return string Local path to the downloaded file
    //  */
    // protected function downloadFile(string $url, string $filename): string
    // {
    //     $path = __DIR__ . '/' . $filename;
    //     if (!file_exists($path)) {
    //         $parsedUrl = parse_url($url);
    //         $host = $parsedUrl['host'];
    //         $remotePath = $parsedUrl['path'];

    //         $conn = ftp_connect($host);
    //         if ($conn) {
    //             $loggedIn = ftp_login($conn, 'anonymous', '');
    //             if ($loggedIn) {
    //                 ftp_pasv($conn, true);
    //                 $downloaded = ftp_get($conn, $path, $remotePath, FTP_BINARY);
    //                 if (!$downloaded) {
    //                     throw new Exception("Failed to download the file '{$url}'.");
    //                 }
    //                 ftp_close($conn);
    //             } else {
    //                 throw new Exception("Failed to log in to the FTP server '{$host}'.");
    //             }
    //         } else {
    //             throw new Exception("Failed to connect to the FTP server '{$host}'.");
    //         }
    //     }

    //     return $path;
    // }
    
    // /**
    //  * Get local path to HapMap Phase II genetic map for hg19 / GRCh37 (HapMapII), downloading if necessary
    //  *
    //  * @return string Path to genetic_map_HapMapII_GRCh37.tar.gz
    //  */
    // public function getPathGeneticMapHapMapIIGRCh37(): string
    // {
    //     return $this->downloadFile(
    //         'ftp://ftp.ncbi.nlm.nih.gov/hapmap/recombination/2011-01_phaseII_B37/genetic_map_HapMapII_GRCh37.tar.gz',
    //         'genetic_map_HapMapII_GRCh37.tar.gz'
    //     );
    // }

    // /**
    //  * Get local path to population-specific 1000 Genomes Project genetic map,
    //  * downloading if necessary.
    //  *
    //  * @param string $pop
    //  * @return string path to {pop}_omni_recombination_20130507.tar
    //  */
    // public function getGeneticMap1000G_GRCh37($pop)
    // {
    //     $filename = "{$pop}_omni_recombination_20130507.tar";
    //     return $this->downloadFile(
    //         "ftp://ftp.1000genomes.ebi.ac.uk/vol1/ftp/technical/working/20130507_omni_recombination_rates/{$filename}",
    //         $filename
    //     );
    // }    

    // /**
    //  * Downloads the knownGene.txt.gz file for the hg19 genome assembly from the UCSC Genome Browser FTP server.
    //  *
    //  * @return string The path to the downloaded file.
    //  */
    // public function get_path_knownGene_hg19(): string {
    //     // Download the file from the UCSC Genome Browser FTP server.
    //     // The file is located at ftp://hgdownload.cse.ucsc.edu/goldenPath/hg19/database/knownGene.txt.gz
    //     // and will be saved as knownGene_hg19.txt.gz in the current directory.
    //     return $this->download_file(
    //         "ftp://hgdownload.cse.ucsc.edu/goldenPath/hg19/database/knownGene.txt.gz",
    //         "knownGene_hg19.txt.gz"
    //     );
    // }

    // /**
    //  * Downloads the kgXref.txt.gz file for the hg19 genome assembly from the UCSC Genome Browser FTP server.
    //  *
    //  * @return string The path to the downloaded file.
    //  */
    // public function get_path_kgXref_hg19(): string {
    //     // Download the file from the UCSC Genome Browser FTP server.
    //     // The file is located at ftp://hgdownload.cse.ucsc.edu/goldenPath/hg19/database/kgXref.txt.gz
    //     // and will be saved as kgXref_hg19.txt.gz in the current directory.
    //     return $this->download_file(
    //         "ftp://hgdownload.cse.ucsc.edu/goldenPath/hg19/database/kgXref.txt.gz",
    //         "kgXref_hg19.txt.gz"
    //     );
    // }    

    

    // public function download_example_datasets() : array
    // {
    //     return [
    //     $this->downloadFile(
    //         "https://opensnp.org/data/662.23andme.340",
    //         "662.23andme.340.txt.gz",
    //         $compress=True
    //     ),
    //     $this->downloadFile(
    //         "https://opensnp.org/data/662.ftdna-illumina.341",
    //         "662.ftdna-illumina.341.csv.gz",
    //         $compress=True
    //     ),
    //     $this->downloadFile(
    //         "https://opensnp.org/data/663.23andme.305",
    //         "663.23andme.305.txt.gz",
    //         $compress=True
    //     ),
    //     $this->downloadFile(
    //         "https://opensnp.org/data/4583.ftdna-illumina.3482",
    //         "4583.ftdna-illumina.3482.csv.gz"
    //     ),
    //     $this->downloadFile(
    //         "https://opensnp.org/data/4584.ftdna-illumina.3483",
    //         "4584.ftdna-illumina.3483.csv.gz"
    //     ),
    //     ];
    // }
}

?>