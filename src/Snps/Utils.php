<?php

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

use Exception;
use ZipArchive;

/**
 * The Singleton class defines the `GetInstance` method that serves as an
 * alternative to constructor and lets clients access the same instance of this
 * class over and over.
 */

// import datetime; // PHP has built-in date functions
// import gzip; // PHP has built-in gzip functions
// import io; // PHP has built-in I/O functions
// import logging; // You can use Monolog or another logging library in PHP
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
    private int $_processes;

    public function __construct(bool $parallelize = false, int $processes = null)
    {
        $this->_parallelize = $parallelize;
        $this->_processes = $processes ?? os_cpu_count();
    }

    public function __invoke(callable $f, array $tasks): array
    {
        if ($this->_parallelize) {
            // Implement parallel (multi-process) execution using pthreads, parallel or another multi-processing library
            // For example, using the parallel extension:
            $runtime = new \parallel\Runtime();
            $promises = array_map(fn($task) => $runtime->run($f, [$task]), $tasks);
            return array_map(fn($promise) => $promise->value(), $promises);
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
    public static function gzip_file($src, $dest)
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
