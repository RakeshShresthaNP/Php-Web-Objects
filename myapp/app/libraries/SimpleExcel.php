<?php

/**
 
 */

final class SimpleExcel
{
    public const CELL_FORMATS = [
        0 => 'General', 1 => '0', 2 => '0.00', 3 => '#,##0', 4 => '#,##0.00',
        9 => '0%', 10 => '0.00%', 11 => '0.00E+00', 12 => '# ?/?', 13 => '# ??/??',
        14 => 'mm-dd-yy', 15 => 'd-mmm-yy', 16 => 'd-mmm', 17 => 'mmm-yy',
        18 => 'h:mm AM/PM', 19 => 'h:mm:ss AM/PM', 20 => 'h:mm', 21 => 'h:mm:ss',
        22 => 'm/d/yy h:mm', 37 => '#,##0 ;(#,##0)', 38 => '#,##0 ;[Red](#,##0)',
        39 => '#,##0.00;(#,##0.00)', 40 => '#,##0.00;[Red](#,##0.00)',
        44 => '_("$"* #,##0.00_);_("$"* \(#,##0.00\);_("$"* "-"??_);_(@_)',
        45 => 'mm:ss', 46 => '[h]:mm:ss', 47 => 'mmss.0', 48 => '##0.0E+0', 49 => '@'
    ];

    public array $cellFormats = [];
    public string|bool $datetimeFormat = 'Y-m-d H:i:s';
    public bool $debug = false;

    private array $sheets = [];
    private array $sheetNames = [];
    private array $sheetFiles = [];
    private ?SimpleXMLElement $styles = null;
    private array $hyperlinks = [];
    private array $sharedStrings = [];
    private int $date1904 = 0;
    private string $error = '';
    private int $errno = 0;
    
    // Internal storage for zip entries
    private array $packageEntries = [];

    public function __construct(?string $filename = null, bool $isData = false, bool $debug = false)
    {
        $this->debug = $debug;
        if ($filename) {
            if ($this->load($filename, $isData)) {
                $this->parse();
            }
        }
    }

    public static function parse(string $filename, bool $isData = false, bool $debug = false): self|bool
    {
        $xlsx = new self($filename, $isData, $debug);
        if ($xlsx->success()) {
            return $xlsx;
        }
        return false;
    }

    /**
     * Loads the XLSX file using ZipArchive
     */
    private function load(string $pathOrData, bool $isData): bool
    {
        $zip = new ZipArchive();
        
        if ($isData) {
            $temp = tmpfile();
            fwrite($temp, $pathOrData);
            $path = stream_get_meta_data($temp)['uri'];
        } else {
            $path = $pathOrData;
        }

        if ($zip->open($path) !== true) {
            $this->setError(1, "Could not open ZIP file: $path");
            return false;
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $this->packageEntries[$stat['name']] = $zip->getFromIndex($i);
        }

        $zip->close();
        return true;
    }

    private function parse(): bool
    {
        $relations = $this->getEntryXML('_rels/.rels');
        if (!$relations) return false;

        foreach ($relations->Relationship as $rel) {
            if (basename((string)$rel['Type']) === 'officeDocument') {
                $workbookPath = $this->resolveTarget('', (string)$rel['Target']);
                $workbook = $this->getEntryXML($workbookPath);

                if (!$workbook) continue;

                if ((int)$workbook->workbookPr['date1904'] === 1) {
                    $this->date1904 = 1;
                }

                $sheetMeta = [];
                foreach ($workbook->sheets->sheet as $s) {
                    $sheetMeta[(string)$s['id']] = (string)$s['name'];
                }

                $wbRelsPath = dirname($workbookPath) . '/_rels/' . basename($workbookPath) . '.rels';
                $wbRels = $this->getEntryXML($wbRelsPath);

                if ($wbRels) {
                    foreach ($wbRels->Relationship as $wrel) {
                        $type = basename((string)$wrel['Type']);
                        $target = $this->resolveTarget(dirname($workbookPath), (string)$wrel['Target']);

                        switch ($type) {
                            case 'worksheet':
                                $id = (string)$wrel['Id'];
                                if (isset($sheetMeta[$id])) {
                                    $index = count($this->sheets);
                                    $this->sheets[$index] = $this->getEntryXML($target);
                                    $this->sheetNames[$index] = $sheetMeta[$id];
                                    $this->sheetFiles[$index] = $target;
                                }
                                break;
                            case 'sharedStrings':
                                $this->parseSharedStrings($target);
                                break;
                            case 'styles':
                                $this->parseStyles($target);
                                break;
                        }
                    }
                }
            }
        }
        return count($this->sheets) > 0;
    }

    private function parseSharedStrings(string $path): void
    {
        $xml = $this->getEntryXML($path);
        if (!$xml) return;
        foreach ($xml->si as $si) {
            $this->sharedStrings[] = $this->extractRichText($si);
        }
    }

    private function parseStyles(string $path): void
    {
        $this->styles = $this->getEntryXML($path);
        if (!$this->styles) return;

        $numFormats = [];
        if (isset($this->styles->numFmts)) {
            foreach ($this->styles->numFmts->numFmt as $nf) {
                $numFormats[(int)$nf['numFmtId']] = (string)$nf['formatCode'];
            }
        }

        if (isset($this->styles->cellXfs)) {
            foreach ($this->styles->cellXfs->xf as $xf) {
                $fid = (int)$xf['numFmtId'];
                $format = $numFormats[$fid] ?? (self::CELL_FORMATS[$fid] ?? '');
                $this->cellFormats[] = ['format' => $format, 'numFmtId' => $fid];
            }
        }
    }

    public function rows(int $sheetIndex = 0): array|bool
    {
        $ws = $this->getWorksheet($sheetIndex);
        if (!$ws) return false;

        [$maxCols, $maxRows] = $this->dimension($sheetIndex);
        $result = array_fill(0, $maxRows, array_fill(0, $maxCols, ''));

        foreach ($ws->sheetData->row as $row) {
            foreach ($row->c as $c) {
                [$x, $y] = $this->getCellIndex((string)$c['r']);
                if ($x !== -1 && $y < $maxRows && $x < $maxCols) {
                    $result[$y][$x] = $this->getCellValue($c);
                }
            }
        }
        return $result;
    }

    private function getCellValue(SimpleXMLElement $cell): mixed
    {
        $type = (string)$cell['t'];
        $val = (string)$cell->v;

        switch ($type) {
            case 's': return $this->sharedStrings[(int)$val] ?? '';
            case 'b': return (bool)$val;
            case 'inlineStr': return $this->extractRichText($cell->is);
            case 'e': return $val; // Error
            case 'd': return $this->formatDate((float)$val);
            default:
                // Check if numeric cell is actually a date via format
                $styleIdx = (int)$cell['s'];
                if (isset($this->cellFormats[$styleIdx])) {
                    $fmt = $this->cellFormats[$styleIdx]['format'];
                    if (preg_match('/[m d y]/i', $fmt)) {
                        return $this->formatDate((float)$val);
                    }
                }
                return is_numeric($val) ? (strpos($val, '.') !== false ? (float)$val : (int)$val) : $val;
        }
    }

    private function formatDate(float $excelTime): string|float
    {
        if (!$this->datetimeFormat) return $excelTime;
        $utcDays = $excelTime - ($this->date1904 ? 0 : 25569);
        // Add 1462 days if 1904 system is used
        if ($this->date1904) $utcDays += 1462;
        
        $ts = round($utcDays * 86400);
        return gmdate($this->datetimeFormat, (int)$ts);
    }

    public function getCellIndex(string $cell = 'A1'): array
    {
        if (preg_match('/([A-Z]+)(\d+)/', $cell, $matches)) {
            $colStr = $matches[1];
            $row = (int)$matches[2];
            $col = 0;
            $len = strlen($colStr);
            for ($i = 0; $i < $len; $i++) {
                $col += (ord($colStr[$i]) - 64) * (26 ** ($len - $i - 1));
            }
            return [$col - 1, $row - 1];
        }
        return [-1, -1];
    }

    private function extractRichText(SimpleXMLElement $is): string
    {
        if (isset($is->t)) return (string)$is->t;
        $res = [];
        foreach ($is->r as $run) {
            $res[] = (string)$run->t;
        }
        return implode('', $res);
    }

    public function getEntryXML(string $name): ?SimpleXMLElement
    {
        $name = ltrim(str_replace('\\', '/', $name), '/');
        $data = $this->packageEntries[$name] ?? null;

        if (!$data) return null;

        // Clean namespaces for easier SimpleXML access
        $data = preg_replace('/xmlns[^=]*="[^"]*"/i', '', $data);
        $data = preg_replace('/[a-zA-Z0-9]+:([a-zA-Z0-9]+)/', '$1', $data);

        return simplexml_load_string($data, 'SimpleXMLElement', LIBXML_NOENT | LIBXML_NONET);
    }

    public function dimension(int $sheetIndex = 0): array
    {
        $ws = $this->getWorksheet($sheetIndex);
        if (!$ws) return [0, 0];

        $ref = (string)$ws->dimension['ref']; // e.g., "A1:C10"
        if (str_contains($ref, ':')) {
            $parts = explode(':', $ref);
            [$x, $y] = $this->getCellIndex($parts[1]);
            return [$x + 1, $y + 1];
        }
        return [1, 1];
    }

    public function getWorksheet(int $index): ?SimpleXMLElement
    {
        return $this->sheets[$index] ?? null;
    }

    public function success(): bool
    {
        return empty($this->error);
    }

    private function setError(int $errno, string $msg): void
    {
        $this->errno = $errno;
        $this->error = $msg;
        if ($this->debug) {
            error_log("SimpleExcel Error [$errno]: $msg");
        }
    }

    private function resolveTarget(string $base, string $target): string
    {
        $target = ltrim(str_replace('\\', '/', $target), '/');
        if (!$base) return $target;
        
        $abs = explode('/', $base . '/' . $target);
        $res = [];
        foreach ($abs as $p) {
            if ($p === '.') continue;
            if ($p === '..') { array_pop($res); }
            else { $res[] = $p; }
        }
        return implode('/', $res);
    }
}
