<?php

namespace AllegedWizard\PhpSpreadsheets\Readers;

abstract class AbstractSpreadsheetReader
{
    protected string $path;
    protected string $extension;
    protected array $columns = [];
    protected mixed $reader;
    protected array $limitColumns = [];
    protected array $unparsable = [];
    protected ?string $tempFolder = null;

    /**
     * @var string Separator for {sheetName}:::{sheetIndex} to support
     * multiple sheets sharing the same name.
     */
    protected string $sheetNameSeparator = ':::';

    public function __construct(string $path)
    {
        $this->path = $path;
        $this->validatePath();

        $extension = explode('.', $path);
        $this->extension = strtolower(array_pop($extension));
    }

    protected function validatePath(): void
    {
        if (!file_exists($this->path)) {
            throw new \Exception('Invalid path specified: ' . $this->path);
        }
    }

    public function setLimitColumns(array $columns): self
    {
        $this->limitColumns = $columns;
        return $this;
    }

    protected function maybeLimitColumns(array $row = []): array
    {
        if (!empty($this->limitColumns)) {
            $output = [];
            foreach ($this->limitColumns as $column) {
                if (str_contains($column, '**')) {
                    $match = str_replace('**', '', $column);
                    foreach ($row as $key => $value) {
                        if (str_starts_with($key, $match)) {
                            $output[$key] = $value;
                        }
                    }
                } else {
                    if (isset($row[$column])) {
                        $output[$column] = $row[$column];
                    }
                }
            }
            return $output;
        }
        return $row;
    }

    protected function getSheetKey(string $sheetName, int $sheetIndex): string
    {
        return sprintf('%s%s%s', $sheetName, $this->sheetNameSeparator, $sheetIndex);
    }

    public function getReader(): mixed
    {
        return null;
    }

    public function getColumns(): array
    {
        return [];
    }

    public function toArray(): array
    {
        return [];
    }

    public function hasMultipleSheets(): bool
    {
        $columns = $this->getColumns();
        return count($columns) > 1;
    }

    public function getSheetName(string $sheetKey): string
    {
        $sheetNamePieces = explode($this->sheetNameSeparator, $sheetKey);
        return $sheetNamePieces[0];
    }

    public function getRowCount(): array
    {
        return [];
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }

    public function setTempFolder(?string $tempFolder): self
    {
        if ($tempFolder !== null && !is_dir($tempFolder)) {
            if (!mkdir($tempFolder, 0777, true) && !is_dir($tempFolder)) {
                throw new \Exception('Unable to create temp folder: ' . $tempFolder);
            }
        }
        $this->tempFolder = $tempFolder;
        return $this;
    }

    public function getTempFolder(): ?string
    {
        return $this->tempFolder;
    }

    public function getUnparsable(): array
    {
        return $this->unparsable;
    }
}
