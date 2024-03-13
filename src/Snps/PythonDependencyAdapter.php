&lt;?php

namespace Dna\Snps;

use MathPHP\LinearAlgebra\Matrix;
use MathPHP\Statistics\Descriptive;
use League\Csv\Reader;
use League\Csv\Writer;

class PythonDependencyAdapter
{
    public function readCsv(string $filePath): array
    {
        $csv = Reader::createFromPath($filePath, 'r');
        $csv->setHeaderOffset(0);
        return $csv->getRecords();
    }

    public function writeCsv(string $filePath, array $data): void
    {
        $csv = Writer::createFromPath($filePath, 'w+');
        $csv->insertOne(array_keys(reset($data)));
        $csv->insertAll($data);
    }

    public function matrixMultiplication(array $matrix1, array $matrix2): Matrix
    {
        $matrixA = new Matrix($matrix1);
        $matrixB = new Matrix($matrix2);
        return $matrixA->multiply($matrixB);
    }

    public function calculateMean(array $data): float
    {
        return Descriptive::mean($data);
    }

    public function filterData(array $data, callable $callback): array
    {
        return array_filter($data, $callback);
    }

    public function mergeData(array $data1, array $data2): array
    {
        return array_merge($data1, $data2);
    }
}
