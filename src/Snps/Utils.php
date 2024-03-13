<?php

/**
 * php-dna.
 *
 * Utility functions for php-dna project.
 *
 * This file contains the Utils class which provides various utility functions
 * for file manipulation, parallel processing, and other common tasks.
 *
 * @author          Devmanateam <devmanateam@outlook.com>
 * @copyright       Copyright (c) 2020-2023, Devmanateam
 * @license         MIT
 * @link            http://github.com/familytree365/php-dna
 */

namespace Dna\Snps;

use Exception;
use ZipArchive;
use parallel\Runtime;
use function shell_exec;
use function fopen;
use function fclose;
use function fread;
use function fwrite;
use function gzopen;
use function gzwrite;
use function gzclose;
use function file_exists;
use function mkdir;
use function gmdate;
use function fputcsv;
use function preg_replace;
use function basename;
// from multiprocessing import Pool; // You can use parallel or pthreads for multi-processing in PHP
// import os; // PHP has built-in OS functions
// import re; // PHP has built-in RegExp functions
// import shutil; // PHP has built-in filesystem functions
// import tempfile; // PHP has built-in temporary file functions
// import zipfile; // PHP has built-in ZipArchive class available

// from atomicwrites import atomic_write; // You can use a library or implement atomic writes in PHP
// import pandas as pd; // There is no direct PHP alternative to pandas; consider using array functions or a data manipulation library
// import snps; // If this is a custom module, you can rewrite it in PHP and load it here

// logger = logging.getLogger(__name__); // Replace this with your preferred logging solution in PHP

class Parallelizer
{
    private bool $_parallelize;
    private ?int $_processes;
    public function __construct(bool $parallelize = false, ?int $processes = null): void
    {
        $this->_parallelize = $parallelize;
/**
 * Utils class provides utility functions for file manipulation, parallel processing,
 * and other common tasks. It includes methods for gzipping files, creating directories,
 * fetching current UTC time, saving data as CSV, cleaning strings, and zipping files.
 */
        $this->_processes = $processes ?? os_cpu_count();
    }

    public function __invoke(callable $f, array $tasks): array
    {
        if ($this->_parallelize) {
            // Parallel execution using the parallel extension. Tasks are distributed across multiple threads.
            // Each task is executed in a separate thread, and the results are collected and returned.
            $runtime = new \parallel\Runtime();
            $futures = [];
            foreach ($tasks as $task) {
                $futures[] = $runtime->run($f, [$task]);
            }
            return array_map(fn($future) => $future->value, $futures);
        } else {
            return array_map($f, $tasks);
        }
    }

    function os_cpu_count(): int
    {
        // Use this function if you need to get the number of CPU cores in PHP
        // You might need to adjust this code based on your environment
        if (substr(php_uname('s'), 0, 7) == 'Windows') {
            return (int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
        } else {
            return (int) shell_exec('nproc');
        }
    }
}

class Utils
{
    public static function gzip_file(string $src, string $dest): string
    {
        /**
         * Gzip a file.
         *
         * @param string $src  Path to file to gzip
         * @param string $dest Path to output gzip file
         *
         * @return string Path to gzipped file
         */

        $bufferSize = 4096;
        $srcFile = fopen($src, "rb");

        if ($srcFile === false) {
            throw new Exception("Cannot open source file");
        }

        try {
            $destFile = fopen($dest, "wb");

            if ($destFile === false) {
                throw new Exception("Cannot create destination file");
            }

            try {
                $gzFile = gzopen($dest, "wb");

                if ($gzFile === false) {
                    throw new Exception("Cannot create gzipped file");
                }

                try {
                    while (!feof($srcFile)) {
                        $buffer = fread($srcFile, $bufferSize);
                        gzwrite($gzFile, $buffer);
                    }
                } finally {
                    gzclose($gzFile);
                }
            } finally {
                fclose($destFile);
            }
        } finally {
            fclose($srcFile);
        }

        return $dest;
    }
}
/**
 * Creates a directory if it doesn't exist.
 *
 * @param string $path Path to the directory to create.
 * @return void
 */
public static function create_dir(string $path): void
{
    if (!file_exists($path)) {
        mkdir($path, 0777, true);
    }
}

/**
 * Gets the current UTC time.
 *
 * @return string Current UTC time in 'Y-m-d H:i:s' format.
 */
public static function get_utc_now(): string
{
    return gmdate('Y-m-d H:i:s');
}

/**
 * Saves data as a CSV file.
 *
 * @param array $data Data to save.
 * @param string $filename Path to the CSV file.
 * @return void
 */
public static function save_df_as_csv(array $data, string $filename): void
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
public static function clean_str(string $str): string
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
public static function zip_file(string $src, string $dest): void
{
    $zip = new ZipArchive();
    if ($zip->open($dest, ZipArchive::CREATE) === TRUE) {
        $zip->addFile($src, basename($src));
        $zip->close();
    }
}
