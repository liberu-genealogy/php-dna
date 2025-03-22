<?php

declare(strict_types=1);

/**
 * php-dna.
 *
 * Utility functions.
 *
 * @author          Devmanateam <devmanateam@outlook.com>
 * @copyright       Copyright (c) 2020-2023, Devmanateam
 * @license         MIT
 *
 * @link            http://github.com/familytree365/php-dna
 */

namespace Dna\Snps;

use ZipArchive;
use Exception;

/**
 * Utils class provides utility functions for file manipulation, parallel processing,
 * and other common tasks. It includes methods for gzipping files, creating directories,
 * fetching current UTC time, saving data as CSV, cleaning strings, and zipping files.
 */
final class Utils
{
    public static function gzipFile(string $src, string $dest): string
    {
        /**
         * Gzip a file.
         *
         * @param string $src  Path to file to gzip
         * @param string $dest Path to output gzip file
         *
         * @return string Path to gzipped file
         */

        if (!is_readable($src)) {
            throw new Exception("Cannot read source file: {$src}");
        }

        $srcFile = fopen($src, "rb");
        $gzFile = gzopen($dest, "wb9"); // Maximum compression

        try {
            stream_copy_to_stream($srcFile, $gzFile);
            return $dest;
        } finally {
            fclose($srcFile);
            gzclose($gzFile);
        }
    }
/**
 * Creates a directory if it doesn't exist.
 *
 * @param string $path Path to the directory to create.
 * @return void
 */
    public static function createDir(string $path): void
    {
        if (!file_exists($path)) {
            mkdir($path, 0755, true);
        }
    }

/**
 * Gets the current UTC time.
 *
 * @return string Current UTC time in 'Y-m-d H:i:s' format.
 */
    public static function getUtcNow(): string
    {
        return (new \DateTimeImmutable('now', new \DateTimeZone('UTC')))
            ->format('Y-m-d H:i:s');
    }

/**
 * Saves data as a CSV file.
 *
 * @param array $data Data to save.
 * @param string $filename Path to the CSV file.
 * @return void
 */
    public static function saveDfAsCsv(array $data, string $filename): void
    {
        $fp = fopen($filename, 'w');
        foreach ($data as $row) {
            fputcsv($fp, $row);
        }
        fclose($fp);
    }

/**
 * Cleans a string to be used as a variable name.
 *
 * @param string $str String to clean.
 * @return string Cleaned string.
 */
    public static function cleanStr(string $str): string
    {
        return preg_replace('/[^A-Za-z0-9_]/', '', $str);
    }

/**
 * Zips a file.
 *
 * @param string $src Path to the file to zip.
 * @param string $dest Path to the output zip file.
 * @return void
 */
    public static function zipFile(string $src, string $dest): void
    {
        $zip = new ZipArchive();
        if ($zip->open($dest, ZipArchive::CREATE) === TRUE) {
            $zip->addFile($src, basename($src));
            $zip->close();
        }
    }
}