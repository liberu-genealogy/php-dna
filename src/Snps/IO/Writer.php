<?php

declare(strict_types=1);

namespace Dna\Snps\IO;

use Dna\Snps\SNPs;

/**
 * Class for writing SNPs data to various formats
 */
class Writer
{
    private SNPs $snps;
    private string $filename;
    private bool $vcf;
    private bool $atomic;
    private string $vcfAltUnavailable;
    private string $vcfChromPrefix;
    private bool $vcfQcOnly;
    private bool $vcfQcFilter;
    private array $kwargs;

    public function __construct(array $config, array $kwargs = [])
    {
        $this->snps = $config['snps'];
        $this->filename = $config['filename'] ?? '';
        $this->vcf = $config['vcf'] ?? false;
        $this->atomic = $config['atomic'] ?? true;
        $this->vcfAltUnavailable = $config['vcf_alt_unavailable'] ?? '.';
        $this->vcfChromPrefix = $config['vcf_chrom_prefix'] ?? '';
        $this->vcfQcOnly = $config['vcf_qc_only'] ?? false;
        $this->vcfQcFilter = $config['vcf_qc_filter'] ?? false;
        $this->kwargs = $kwargs;
    }

    /**
     * Write SNPs data to file
     *
     * @return array [path, extra_data]
     */
    public function write(): array
    {
        if (empty($this->filename)) {
            $this->filename = $this->generateFilename();
        }

        $snpsData = $this->snps->getSnps();

        if (empty($snpsData)) {
            return ['', []];
        }

        if ($this->vcf) {
            return $this->writeVcf($snpsData);
        } else {
            return $this->writeCsv($snpsData);
        }
    }

    /**
     * Write data as CSV/TSV format
     */
    private function writeCsv(array $snpsData): array
    {
        $separator = $this->kwargs['sep'] ?? "\t";
        $path = $this->ensureExtension($this->filename, $separator === ',' ? '.csv' : '.tsv');

        $handle = fopen($path, 'w');
        if (!$handle) {
            return ['', []];
        }

        // Write header
        fputcsv($handle, ['rsid', 'chrom', 'pos', 'genotype'], $separator);

        // Write data
        foreach ($snpsData as $rsid => $snp) {
            fputcsv($handle, [
                $rsid,
                $snp['chrom'] ?? '',
                $snp['pos'] ?? '',
                $snp['genotype'] ?? ''
            ], $separator);
        }

        fclose($handle);
        return [$path, []];
    }

    /**
     * Write data as VCF format
     */
    private function writeVcf(array $snpsData): array
    {
        $path = $this->ensureExtension($this->filename, '.vcf');

        $handle = fopen($path, 'w');
        if (!$handle) {
            return ['', []];
        }

        // Write VCF header
        $this->writeVcfHeader($handle);

        $discrepantPositions = [];

        // Write data
        foreach ($snpsData as $rsid => $snp) {
            $vcfLine = $this->formatVcfLine($rsid, $snp);
            if ($vcfLine) {
                fwrite($handle, $vcfLine . "\n");
            } else {
                $discrepantPositions[] = $snp;
            }
        }

        fclose($handle);
        return [$path, $discrepantPositions];
    }

    /**
     * Write VCF header
     */
    private function writeVcfHeader($handle): void
    {
        fwrite($handle, "##fileformat=VCFv4.2\n");
        fwrite($handle, "##source=php-dna\n");
        fwrite($handle, "##assembly=" . $this->snps->getAssembly() . "\n");
        fwrite($handle, "#CHROM\tPOS\tID\tREF\tALT\tQUAL\tFILTER\tINFO\tFORMAT\tSAMPLE\n");
    }

    /**
     * Format a single VCF line
     */
    private function formatVcfLine(string $rsid, array $snp): ?string
    {
        $chrom = $this->vcfChromPrefix . ($snp['chrom'] ?? '');
        $pos = $snp['pos'] ?? '';
        $genotype = $snp['genotype'] ?? '';

        if (empty($chrom) || empty($pos) || empty($genotype)) {
            return null;
        }

        // Simple VCF format - in a full implementation, this would need
        // reference genome lookup for REF/ALT alleles
        $ref = strlen($genotype) > 0 ? $genotype[0] : 'N';
        $alt = strlen($genotype) > 1 ? $genotype[1] : $this->vcfAltUnavailable;

        if ($ref === $alt) {
            $alt = $this->vcfAltUnavailable;
        }

        $qual = '.';
        $filter = $this->vcfQcFilter ? 'PASS' : '.';
        $info = '.';
        $format = 'GT';
        $sample = $ref === $alt ? '0/0' : '0/1';

        return implode("\t", [$chrom, $pos, $rsid, $ref, $alt, $qual, $filter, $info, $format, $sample]);
    }

    /**
     * Generate a filename if none provided
     */
    private function generateFilename(): string
    {
        $timestamp = date('Y-m-d_H-i-s');
        $extension = $this->vcf ? '.vcf' : '.tsv';
        return "snps_output_{$timestamp}{$extension}";
    }

    /**
     * Ensure filename has the correct extension
     */
    private function ensureExtension(string $filename, string $extension): string
    {
        if (!str_ends_with($filename, $extension)) {
            $filename .= $extension;
        }
        return $filename;
    }
}