<?php

namespace App\Helpers\SeederHelpers;

use Illuminate\Support\Facades\Log;

class JsonDataModifier
{
    /**
     * The module name (e.g., 'blog', 'appointment', 'donation')
     * Empty string for root-level seed data.
     */
    private string $module;

    /**
     * The data type / file name (e.g., 'blog-category', 'language')
     */
    private string $dataType;

    /**
     * The decoded JSON data.
     */
    private array $data;

    /**
     * Base path for seed data JSON files.
     */
    private const BASE_PATH = 'assets/tenant/seed-data';

    /**
     * @param string $module   The module name (empty string for root-level data)
     * @param string $dataType The data type / JSON filename (without .json extension)
     */
    public function __construct(string $module, string $dataType)
    {
        $this->module = $module;
        $this->dataType = $dataType;
        $this->data = $this->loadJsonData();
    }

    /**
     * Load the JSON data from the file system.
     *
     * @return array
     */
    private function loadJsonData(): array
    {
        $filePath = $this->getFilePath();

        if (!file_exists($filePath)) {
            Log::warning("JsonDataModifier: Seed data file not found: {$filePath}");
            return [];
        }

        $contents = file_get_contents($filePath);

        if ($contents === false) {
            Log::error("JsonDataModifier: Failed to read seed data file: {$filePath}");
            return [];
        }

        $decoded = json_decode($contents, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Log::error("JsonDataModifier: Invalid JSON in file {$filePath}: " . json_last_error_msg());
            return [];
        }

        return $decoded;
    }

    /**
     * Build the file path for the JSON data file.
     *
     * @return string
     */
    private function getFilePath(): string
    {
        $basePath = base_path(self::BASE_PATH);

        if (!empty($this->module)) {
            return $basePath . '/' . $this->module . '/' . $this->dataType . '.json';
        }

        return $basePath . '/' . $this->dataType . '.json';
    }

    /**
     * Get column data from the JSON file.
     * Returns an array of associative arrays with only the specified columns.
     *
     * @param array $columns The column names to extract from each record
     * @return array
     */
    public function getColumnData(array $columns): array
    {
        if (empty($this->data)) {
            return [];
        }

        return array_map(function ($item) use ($columns) {
            $row = [];
            foreach ($columns as $column) {
                $row[$column] = $item[$column] ?? null;
            }
            return $row;
        }, $this->data);
    }

    /**
     * Get column data specifically for dynamic pages.
     * Supports optional array conversion and unique filtering.
     *
     * @param array $columns          The column names to extract
     * @param bool  $convertToArray   Whether to convert objects to arrays
     * @param bool  $unique           Whether to filter for unique records
     * @return array
     */
    public function getColumnDataForDynamicPage(array $columns, bool $convertToArray = false, bool $unique = false): array
    {
        if (empty($this->data)) {
            return [];
        }

        $result = array_map(function ($item) use ($columns) {
            $row = [];
            foreach ($columns as $column) {
                $value = $item[$column] ?? null;

                // Handle page_content which may be stored as JSON string
                if ($column === 'page_content' && is_string($value)) {
                    $row[$column] = $value;
                } else {
                    $row[$column] = $value;
                }
            }
            return $row;
        }, $this->data);

        if ($unique) {
            $seen = [];
            $result = array_filter($result, function ($item) use (&$seen) {
                $key = $item['slug'] ?? $item['id'] ?? serialize($item);
                if (isset($seen[$key])) {
                    return false;
                }
                $seen[$key] = true;
                return true;
            });
        }

        return array_values($result);
    }

    /**
     * Get raw data from the JSON file.
     *
     * @return array
     */
    public function getRawData(): array
    {
        return $this->data;
    }
}
