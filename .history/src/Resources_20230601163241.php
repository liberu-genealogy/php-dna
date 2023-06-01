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
class Resources extends \Dna\Snps\SNPsResources
{

  protected $_genetic_map = '{}';
  protected $_genetic_map_name = '';
  protected $_cytoBand_hg19 = [];
  protected $_knownGene_hg19 = [];
  protected $_kgXref_hg19 = [];

  public function __construct($resources_dir = 'resources')
    {
      parent::__construct($resources_dir = $resources_dir);
    }
}

public function  _load_genetic_map_HapMapII_GRCh37($filename){
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