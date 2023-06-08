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

/**
 * The Singleton class defines the `GetInstance` method that serves as an
 * alternative to constructor and lets clients access the same instance of this
 * class over and over.
 */
class Utils
{
}

use ZipArchive;

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

class Parallelizer {
    private bool $_parallelize;
    private int $_processes;

    public function __construct(bool $parallelize = false, int $processes = null) {
        $this->_parallelize = $parallelize;
        $this->_processes = $processes ?? os_cpu_count();
    }

    public function __invoke(callable $f, array $tasks): array {
        if ($this->_parallelize) {
            // Implement parallel (multi-process) execution using pthreads, parallel or another multi-processing library
            // For example, using the parallel extension:
            // $runtime = new \parallel\Runtime();
            // $promises = [];
            // foreach ($tasks as $task) {
            //     $promises[] = $runtime->run($f, [$task]);
            // }
            // return array_map(fn($promise) => $promise->value(), $promises);
        } else {
            return array_map($f, $tasks);
        }
    }
}

function os_cpu_count(): int {
    // Use this function if you need to get the number of CPU cores in PHP
    // You might need to adjust this code based on your environment
    if (substr(php_uname('s'), 0, 7) == 'Windows') {
        return (int) shell_exec('echo %NUMBER_OF_PROCESSORS%');
    } else {
        return (int) shell_exec('nproc');
    }
}


class Singleton {
    private static array $instances = [];

    public static function getInstance(): self {
        $cls = static::class;
        if (!isset(self::$instances[$cls])) {
            self::$instances[$cls] = new static();
        }
        return self::$instances[$cls];
    }

    protected function __construct() {}
    private function __clone() {}
    private function __wakeup() {}
}

function create_dir(string $path): bool {
    if (!file_exists($path)) {
        // Create directory if it doesn't exist
        if (!mkdir($path, 0777, true)) {
            return false;
        }
    }
    return true;
}

?>
