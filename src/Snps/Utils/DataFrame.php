<?php

namespace Dna\Snps\Utils;

class DataFrame {
    private $data;

    public function __construct(array $data = []) {
        $this->data = $data;
    }

    public function filter(callable $callback): DataFrame {
        $filteredData = array_filter($this->data, $callback, ARRAY_FILTER_USE_BOTH);
        return new self($filteredData);
    }

    public function sort(array $columns, array $directions = []): DataFrame {
        $sortedData = $this->data;
        usort($sortedData, function ($a, $b) use ($columns, $directions) {
            foreach ($columns as $index => $column) {
                $direction = $directions[$index] ?? SORT_ASC;
                if ($a[$column] == $b[$column]) {
                    continue;
                }
                return ($a[$column] < $b[$column]) ? -1 * $direction : 1 * $direction;
            }
            return 0;
        });
        return new self($sortedData);
    }

    public function merge(DataFrame $other, string $on, string $type = 'inner'): DataFrame {
        // Simplified merge function for demonstration purposes
        $mergedData = []; // Implement merging logic based on $type (inner, left, right, outer)
        return new self($mergedData);
    }

    public function getData(): array {
        return $this->data;
    }
}
