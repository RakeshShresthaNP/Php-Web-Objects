<?php
declare(strict_types = 1);
namespace ML\Dataset;

use ML\Exception\FileException;

class CsvDataset extends ArrayDataset
{

    /**
     *
     * @var array
     */
    protected $columnNames = [];

    /**
     *
     * @throws FileException
     */
    public function __construct(string $filepath, int $features, bool $headingRow = false, string $delimiter = ',', int $maxLineLength = 0)
    {
        $enclosure = "\"";
        $escape = "\\";

        if (! file_exists($filepath)) {
            throw new FileException(sprintf('File "%s" missing.', basename($filepath)));
        }

        $handle = fopen($filepath, 'rb');
        if ($handle === false) {
            throw new FileException(sprintf('File "%s" can\'t be open.', basename($filepath)));
        }

        if ($headingRow) {
            $data = fgetcsv($handle, $maxLineLength, $delimiter, $enclosure, $escape);
            $this->columnNames = array_slice((array) $data, 0, $features);
        } else {
            $this->columnNames = range(0, $features - 1);
        }

        $samples = $targets = [];
        while ($data = fgetcsv($handle, $maxLineLength, $delimiter, $enclosure, $escape)) {
            $samples[] = array_slice($data, 0, $features);
            $targets[] = $data[$features];
        }

        fclose($handle);

        parent::__construct($samples, $targets);
    }

    public function getColumnNames(): array
    {
        return $this->columnNames;
    }
}
