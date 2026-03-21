<?php

namespace AllegedWizard\PhpSpreadsheets\Writers;

abstract class AbstractSpreadsheetWriter
{
    protected string $path;
    protected string $extension;

    public function __construct(string $path)
    {
        $this->validatePath($path);
    }

    protected function validatePath(string $path): void
    {
        $extension = explode('.', $path);
        $this->extension = array_pop($extension);

        if (file_exists($path)) {
            $this->path = $path;
            return;
        }

        $dirname = dirname($path);
        if (!is_dir($dirname)) {
            $created = mkdir($dirname, 0777, true);
            if (!$created) {
                throw new \Exception('Unable to create directory: ' . $dirname);
            }
        }

        $this->path = $path;
    }

    public function write(array $records = [], array $model = [], $multipleSheets = false, bool $optimizedColumns = false): void
    {
        throw new \Exception('Override write()');
    }

    public function setExtension(string $extension): void
    {
        $this->extension = $extension;
    }
}
