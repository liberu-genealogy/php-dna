<?php

namespace Dna\Snps\IO;

class CsvReader
{
    private $filePath;
    private $separator;
    private $header;
    private $columnNames;
    private $columnTypes;

    public function __construct($filePath)
    {
        $this->filePath = $filePath;
        $this->separator = "\s+";
        $this->header = false;
        $this->columnNames = [];
        $this->columnTypes = [];
    }

    public function setSeparator($separator)
    {
        $this->separator = $separator;
    }

    public function setHeader($header)
    {
        $this->header = $header;
    }

    public function setColumnNames($columnNames)
    {
        $this->columnNames = $columnNames;
    }

    public function setColumnTypes($columnTypes)
    {
        $this->columnTypes = $columnTypes;
    }

    public function read()
    {
        $data = [];

        if (($handle = fopen($this->filePath, "r")) !== false) {
            if ($this->header) {
                fgetcsv($handle, 0, $this->separator); // Skip the header row
            }

            while (($row = fgetcsv($handle, 0, $this->separator)) !== false) {
                if (!empty($this->columnNames)) {
                    $row = array_combine($this->columnNames, $row);
                }

                if (!empty($this->columnTypes)) {
                    foreach ($this->columnTypes as $column => $type) {
                        settype($row[$column], $type);
                    }
                }

                $data[] = $row;
            }

            fclose($handle);
        }

        return $data;
    }
}
