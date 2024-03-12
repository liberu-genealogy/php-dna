<?php

namespace src\Helpers;

class CSVGenerator
{
    public static function generate(array $data, string $filePath)
    {
        if (!is_writable(dirname($filePath))) {
            throw new \Exception("Directory is not writable: " . dirname($filePath));
        }

        $fileHandle = fopen($filePath, 'w');
        if (!$fileHandle) {
            throw new \Exception("Failed to open file for writing: " . $filePath);
        }

        try {
            foreach ($data as $row) {
                fputcsv($fileHandle, $row);
            }
        } finally {
            fclose($fileHandle);
        }
    }
}
