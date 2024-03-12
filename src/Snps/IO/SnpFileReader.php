<?php

namespace Dna\Snps\IO;

use Dna\Snps\Resources;
use Dna\Snps\Ensembl;

class SnpFileReader
{
    private ?Resources $resources;
    private ?Ensembl $ensemblRestClient;

    public function __construct(?Resources $resources = null, ?Ensembl $ensemblRestClient = null)
    {
        $this->resources = $resources;
        $this->ensemblRestClient = $ensemblRestClient;
    }

    public function readRawData(string $file, bool $only_detect_source = false, array $rsids = []): array
    {
        $reader = new Reader($file, $only_detect_source, $this->resources, $rsids);
        $data = $reader->read();

        return [
            'snps' => $data['snps'],
            'source' => $data['source'],
            'phased' => $data['phased'],
            'build' => $data['build'],
        ];
    }

    public function readFile(string $file): array
    {
        $data = $this->readRawData($file);

        if (!empty($data)) {
            // Further processing of the data if necessary
            // For example, sorting, deduplication, etc.
        }

        return $data;
    }
}
