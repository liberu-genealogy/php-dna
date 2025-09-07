declare(strict_types=1);

namespace Dna;

use Dna\Snps\SNPs;
use ReflectionMethod;

final class Individual extends SNPs
{
    public function __construct(
        private readonly string $name,
        private readonly mixed $rawData = [],
        private readonly array $kwargs = []
    ) {
        $snpsConstructorArgs = $this->getDefinedKwargs(
            new ReflectionMethod(SNPs::class, '__construct'),
            $kwargs
        );

        parent::__construct(...array_values($snpsConstructorArgs));

        $this->processRawData();
    }

    private function processRawData(): void
    {
        $rawDataArray = is_array($this->rawData) ? $this->rawData : [$this->rawData];

        foreach ($rawDataArray as $data) {
            $snps = $this->createSnpsObject($data);
            $this->merge([$snps]);
        }
    }

    /**
     * Get the string representation of the Individual
     *
     * @return string The string representation
     */
    public function __toString(): string
    {
        return sprintf("Individual('%s')", $this->name);
    }

    /**
     * Get the Individual's name
     *
     * @return string The name
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Get a variable-safe version of the Individual's name
     *
     * @return string The variable-safe name
     */
    public function getVarName(): string
    {
        return $this->clean_str($this->name);
    }

    /**
     * Clean a string to make it variable-safe
     *
     * @param string $str The string to clean
     * @return string The cleaned string
     */
    private function clean_str(string $str): string
    {
        // Remove special characters and replace with underscores
        $cleaned = preg_replace('/[^a-zA-Z0-9_]/', '_', $str);

        // Remove multiple consecutive underscores
        $cleaned = preg_replace('/_+/', '_', $cleaned);

        // Remove leading/trailing underscores
        $cleaned = trim($cleaned, '_');

        // Ensure it doesn't start with a number
        if (is_numeric(substr($cleaned, 0, 1))) {
            $cleaned = 'var_' . $cleaned;
        }

        return $cleaned ?: 'unnamed';
    }
}