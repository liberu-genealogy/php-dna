<?php

/**
 * php-dna.
 *
 * tools for genetic genealogy and the analysis of consumer DNA test results
 *
 * @author          Devmanateam <devmanateam@outlook.com>
 * @copyright       Copyright (c) 2020-2023, Devmanateam
 * @license         MIT
 *
 * @link            http://github.com/familytree365/php-dna
 */

namespace Dna;

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

    /**
     * Function to get a genetic map based on the given map name
     *
     * @param string $genetic_map - Name of the genetic map to retrieve
     * @return array - Returns an array containing the genetic map
     */
    public function get_genetic_map(string $genetic_map): array
    {
        // Define an array of valid genetic map names
        $valid_genetic_maps = [
            "HapMap2",
            "ACB",
            "ASW",
            "CDX",
            "CEU",
            "CHB",
            "CHS",
            "CLM",
            "FIN",
            "GBR",
            "GIH",
            "IBS",
            "JPT",
            "KHV",
            "LWK",
            "MKK",
            "MXL",
            "PEL",
            "PUR",
            "TSI",
            "YRI",
        ];
        
        // Check if the given genetic map is valid
        if (!in_array($genetic_map, $valid_genetic_maps, true)) {
            error_log("Invalid genetic map");
            return [];
        }

        // If given genetic map is "HapMap2", retrieve the genetic map using another function
        if ($genetic_map === "HapMap2") {
            return $this->get_genetic_map_HapMapII_GRCh37();
        }
        
        // If given genetic map is not "HapMap2", retrieve the genetic map using another function
        return $this->get_genetic_map_1000G_GRCh37($genetic_map);
    }

    /**
     * Function to get the HapMap2 genetic map in GRCh37 format
     *
     * @return array - Returns an array containing the HapMap2 genetic map in GRCh37 format
     */
    public function get_genetic_map_HapMapII_GRCh37(): array
    {  
        // Check if the current genetic map is already HapMap2
        if ($this->_genetic_map_name !== "HapMap2") {
            // If not already HapMap2, load the HapMap2 genetic map and set it as the current genetic map
            $this->_genetic_map = $this->_load_genetic_map_HapMapII_GRCh37(
                $this->_get_path_genetic_map_HapMapII_GRCh37()
            );
            $this->_genetic_map_name = "HapMap2";
        }

        // Return the HapMap2 genetic map in GRCh37 format
        return $this->_genetic_map;
    }

    /**
     * Returns the genetic map for a given population in the 1000 Genomes Project GRCh37 reference genome.
     *
     * @param string $pop The population code (e.g. "CEU", "YRI", "CHB") for which to retrieve the genetic map.
     * @return array The genetic map for the specified population.
     */
    public function get_genetic_map_1000G_GRCh37(string $pop): array
    {
        // Check if the requested genetic map is already loaded
        if ($this->_genetic_map_name !== $pop) {
            // If not, load the genetic map from file
            $this->_genetic_map = $this->_load_genetic_map_1000G_GRCh37(
                $this->_get_path_genetic_map_1000G_GRCh37(pop: $pop)
            );
            // Update the name of the loaded genetic map
            $this->_genetic_map_name = $pop;
        }

        // Return the loaded genetic map
        return $this->_genetic_map;
    }

    /**
     * Returns the cytogenetic banding information for the hg19 reference genome.
     *
     * @return array The cytogenetic banding information for hg19.
     */
    public function get_cytoBand_hg19(): array
    {
        // Check if the cytogenetic banding information for hg19 is already loaded
        if (empty($this->_cytoBand_hg19)) {
            // If not, load the cytogenetic banding information from file
            $this->_cytoBand_hg19 = $this->_loadCytoBand(path: $this->_getPathCytoBandHg19());
        }

        // Return the loaded cytogenetic banding information for hg19
        return $this->_cytoBand_hg19;
    }
    
    /**
     * Returns the knownGene_hg19 data.
     *
     * @return array The knownGene_hg19 data.
     */
    public function get_knownGene_hg19() {
        // Check if the _knownGene_hg19 property is empty.
        if ($this->_knownGene_hg19->empty()) {
            // If it is empty, load the knownGene_hg19 data from the file path.
            $this->_knownGene_hg19 = $this->_load_knownGene(
                $this->_get_path_knownGene_hg19()
            );
        }
        // Return the knownGene_hg19 data.
        return $this->_knownGene_hg19;
    }

    /**
     * Returns the kgXref_hg19 data.
     *
     * @return array The kgXref_hg19 data.
     */
    public function get_kgXref_hg19() {
        // Check if the _kgXref_hg19 property is empty.
        if ($this->_kgXref_hg19->empty()) {
            // If it is empty, load the kgXref_hg19 data from the file path.
            $this->_kgXref_hg19 = $this->_load_kgXref(
                $this->_get_path_kgXref_hg19()
            );
        }
        // Return the kgXref_hg19 data.
        return $this->_kgXref_hg19;
    }    

    public function _load_genetic_map_HapMapII_GRCh37($filename) 
    {
        $genetic_map = array(  );
        $archive = new PharData($filename);
        foreach($archive as $file) {
            if (strpos("genetic_map",$file["name"])===true){
                $df = array(  );
                if (($handle = fopen($file, "r")) !== FALSE) {
                    while (($data = fgetcsv($handle,"\t")) !== FALSE) {
                        $df["Position(bp)"]=$data["pos"];
                        $df["Rate(cM/Mb)"]=$data["rate"];
                        $df["Map(cM)"]=$data["map"];
                    }
                    fclose($handle);
                }
                $start_pos = strpos($file["name"],"chr") + 3;
                $end_pos = strpos($file["name"],".");
                $genetic_map[substr($file["name"],$start_pos,$end_pos)] = $df;
            }
        }
        $genetic_map["X"] = array_merge(
            $genetic_map["X_par1"], $genetic_map["X"], $genetic_map["X_par2"]
        );
        $genetic_map["X_par1"]=array( );
        $genetic_map["X_par2"]=array( );
        return $genetic_map;
    }

    /**
     * Loads a genetic map from a file in the 1000 Genomes Project format (GRCh37).
     *
     * @param string $filename The path to the file to load.
     * @return array An associative array of genetic maps, keyed by chromosome.
     */
    function _load_genetic_map_1000G_GRCh37($filename) {
        $genetic_map = []; // Initialize an empty array to hold the genetic maps.

        $phar = new PharData($filename); // Create a new PharData object from the file.

        foreach ($phar as $member) { // Loop through each file in the Phar archive.
            $filepath = $member->getPathname(); // Get the path to the file.

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

            $chrom = explode("-", $member->getFilename())[1]; // Get the chromosome number from the filename.
            $genetic_map[$chrom] = $df; // Add the genetic map data to the $genetic_map array, keyed by chromosome.
        }

        return $genetic_map; // Return the $genetic_map array.
    }    

    public function _download_file($url, $filename, $compress=False, $timeout=30) 
    {
        if(strpos($url, "ftp://") !== false) {
        $url=str_replace($url,"ftp://", "http://");
        } 
        if ($compress && substr($filename,strlen($filename)-3,strlen($filename)) != ".gz"){
        $filename = $filename+".gz";
        }
        $destination = join($this->resources_dir, $filename);

        if (!mkdir($destination)){
        return "";
        }
        if (file_exists($destination)) {            
        $file_url = $destination;
        header('Content-Type: application/octet-stream');
        header('Content-Description: File Transfer');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Expires: 0');
        header('Cache-Control: must-revalidate');
        header('Pragma: public');
        header('Content-Length: ' . filesize($file_url));
        readfile($file_url);

        // if $compress
        //     $this->_write_data_to_gzip(f, data)
        // else
        //     f.write(data)           
        }   
        return $destination;
    }

    

    public function get_all_resources() 
    {
        $resources = array( );
        $resources[
            "genetic_map_HapMapII_GRCh37"
        ] = $this->get_genetic_map_HapMapII_GRCh37();
        $resources["cytoBand_hg19"] = $this->get_cytoBand_hg19();
        $resources["knownGene_hg19"] = $this->get_knownGene_hg19();
        $resources["kgXref_hg19"] = $this->get_kgXref_hg19();
        return $resources;
    }

    public function download_example_datasets()
    {
        return [
        $this->_download_file(
            "https://opensnp.org/data/662.23andme.340",
            "662.23andme.340.txt.gz",
            $compress=True
        ),
        $this->_download_file(
            "https://opensnp.org/data/662.ftdna-illumina.341",
            "662.ftdna-illumina.341.csv.gz",
            $compress=True
        ),
        $this->_download_file(
            "https://opensnp.org/data/663.23andme.305",
            "663.23andme.305.txt.gz",
            $compress=True
        ),
        $this->_download_file(
            "https://opensnp.org/data/4583.ftdna-illumina.3482",
            "4583.ftdna-illumina.3482.csv.gz"
        ),
        $this->_download_file(
            "https://opensnp.org/data/4584.ftdna-illumina.3483",
            "4584.ftdna-illumina.3483.csv.gz"
        ),
        ];
    }
}

?>