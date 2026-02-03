<?php

declare(strict_types=1);

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * SafeguardTenantMigrations Command
 *
 * Adds Schema::hasTable checks to all tenant and module migrations
 * to prevent "table already exists" errors during re-runs.
 */
class SafeguardTenantMigrations extends Command
{
    protected $signature = 'migrations:safeguard 
                            {--dry-run : Show what would be changed without modifying files}
                            {--modules-only : Only process module migrations}
                            {--tenant-only : Only process tenant migrations}';

    protected $description = 'Add Schema::hasTable checks to tenant and module migrations to make them idempotent';

    private int $processedCount = 0;
    private int $skippedCount = 0;
    private int $errorCount = 0;

    public function handle(): int
    {
        $this->info('🔧 Safeguarding migrations with Schema::hasTable checks...');
        $this->newLine();

        $isDryRun = $this->option('dry-run');
        $modulesOnly = $this->option('modules-only');
        $tenantOnly = $this->option('tenant-only');

        if ($isDryRun) {
            $this->warn('DRY RUN MODE - No files will be modified');
            $this->newLine();
        }

        $paths = [];

        // Tenant migrations
        if (!$modulesOnly) {
            $tenantPath = database_path('migrations/tenant');
            if (is_dir($tenantPath)) {
                $paths['Tenant'] = $tenantPath;
            }
        }

        // Module migrations
        if (!$tenantOnly) {
            $modulesPath = base_path('Modules');
            if (is_dir($modulesPath)) {
                foreach (File::directories($modulesPath) as $moduleDir) {
                    $migrationPath = $moduleDir . '/Database/Migrations';
                    if (is_dir($migrationPath)) {
                        $moduleName = basename($moduleDir);
                        $paths["Module: {$moduleName}"] = $migrationPath;
                    }
                }
            }
        }

        foreach ($paths as $label => $path) {
            $this->info("Processing {$label}...");
            $this->processMigrations($path, $isDryRun);
        }

        $this->newLine();
        $this->info("✅ Processed: {$this->processedCount}");
        $this->info("⏭️  Skipped (already safe): {$this->skippedCount}");
        
        if ($this->errorCount > 0) {
            $this->error("❌ Errors: {$this->errorCount}");
        }

        return self::SUCCESS;
    }

    private function processMigrations(string $path, bool $isDryRun): void
    {
        $files = File::glob($path . '/*.php');

        foreach ($files as $file) {
            $this->processMigrationFile($file, $isDryRun);
        }
    }

    private function processMigrationFile(string $filePath, bool $isDryRun): void
    {
        $filename = basename($filePath);
        $content = File::get($filePath);

        // Skip if already has hasTable check
        if (str_contains($content, 'Schema::hasTable') || str_contains($content, '!Schema::hasTable')) {
            $this->skippedCount++;
            return;
        }

        $updated = false;
        $newContent = $content;

        // Find ALL Schema::create calls (including dynamic table names like $tableNames['xxx'])
        // Pattern for static table names: Schema::create('table_name', ...
        // Pattern for dynamic table names: Schema::create($tableNames['xxx'], ...
        $createPattern = '/Schema::create\s*\(\s*([^,]+)\s*,\s*function\s*\(\s*(?:Blueprint\s+)?\$\w+\s*\)(?:\s*use\s*\([^)]*\))?\s*\{/';
        
        if (preg_match_all($createPattern, $newContent, $matches, PREG_OFFSET_CAPTURE)) {
            // Process in reverse order to preserve offsets
            for ($i = count($matches[0]) - 1; $i >= 0; $i--) {
                $fullMatch = $matches[0][$i][0];
                $tableNameExpr = trim($matches[1][$i][0]);
                $offset = $matches[0][$i][1];
                
                // Find the closing of this Schema::create block
                $endPos = $this->findClosingBrace($newContent, $offset + strlen($fullMatch) - 1);
                if ($endPos === false) continue;
                
                // Find the semicolon after the closing
                $semiPos = strpos($newContent, ';', $endPos);
                if ($semiPos === false) continue;
                
                $createBlock = substr($newContent, $offset, $semiPos - $offset + 1);
                
                // Build the hasTable check expression
                if (preg_match('/^[\'"]([^\'"]+)[\'"]$/', $tableNameExpr, $staticMatch)) {
                    // Static table name
                    $checkExpr = "!Schema::hasTable('{$staticMatch[1]}')";
                } else {
                    // Dynamic table name (variable)
                    $checkExpr = "!Schema::hasTable({$tableNameExpr})";
                }
                
                // Indent the create block
                $indentedBlock = preg_replace('/^/m', '    ', $createBlock);
                $wrappedBlock = "if ({$checkExpr}) {\n        {$indentedBlock}\n        }";
                
                $newContent = substr($newContent, 0, $offset) . $wrappedBlock . substr($newContent, $semiPos + 1);
                $updated = true;
            }
        }

        // Find alter table migrations (add column, etc.)
        if (!$updated && preg_match('/Schema::table\s*\(\s*[\'"]([^\'"]+)[\'"]\s*,/m', $content, $matches)) {
            $tableNameOnly = $matches[1];
            
            $newContent = $this->wrapTableWithHasTable($content, $tableNameOnly);
            $updated = ($newContent !== $content);
        }

        if ($updated && $newContent !== $content) {
            if ($isDryRun) {
                $this->line("  <fg=yellow>Would update:</> {$filename}");
            } else {
                File::put($filePath, $newContent);
                $this->line("  <fg=green>Updated:</> {$filename}");
            }
            $this->processedCount++;
        } else {
            $this->skippedCount++;
        }
    }

    /**
     * Find the closing brace that matches the opening brace at position
     */
    private function findClosingBrace(string $content, int $openPos): int|false
    {
        $depth = 1;
        $len = strlen($content);
        
        for ($i = $openPos + 1; $i < $len; $i++) {
            if ($content[$i] === '{') {
                $depth++;
            } elseif ($content[$i] === '}') {
                $depth--;
                if ($depth === 0) {
                    // Find the outer closing ) for Schema::create(...)
                    for ($j = $i + 1; $j < $len; $j++) {
                        if ($content[$j] === ')') {
                            return $j;
                        }
                        if (!ctype_space($content[$j])) {
                            break;
                        }
                    }
                    return $i;
                }
            }
        }
        
        return false;
    }

    /**
     * Wrap Schema::create with if (!Schema::hasTable) check
     */
    private function wrapCreateWithHasTable(string $content, string $tableName): string
    {
        // Pattern to match Schema::create block
        $pattern = '/(public\s+function\s+up\s*\([^)]*\)\s*(?::\s*void\s*)?\{)([\s\S]*?)(Schema::create\s*\(\s*[\'"]' . preg_quote($tableName, '/') . '[\'"]\s*,\s*function\s*\([^)]*\)\s*\{[\s\S]*?\}\s*\)\s*;)/';
        
        $replacement = '$1$2if (!Schema::hasTable(\'' . $tableName . '\')) {
            $3
        }';

        $newContent = preg_replace($pattern, $replacement, $content, 1);
        
        // If regex failed, try simpler approach
        if ($newContent === $content || $newContent === null) {
            $simplePattern = '/(Schema::create\s*\(\s*[\'"]' . preg_quote($tableName, '/') . '[\'"]\s*,\s*function\s*\([^)]*\)\s*\{[\s\S]*?\}\s*\)\s*;)/';
            
            $newContent = preg_replace_callback($simplePattern, function ($matches) use ($tableName) {
                $indent = '        ';
                $createBlock = $matches[1];
                // Indent the create block
                $indentedBlock = preg_replace('/^/m', '    ', $createBlock);
                return "if (!Schema::hasTable('{$tableName}')) {\n{$indent}{$indentedBlock}\n        }";
            }, $content, 1);
        }

        return $newContent ?? $content;
    }

    /**
     * Wrap Schema::table with if (Schema::hasTable) check
     */
    private function wrapTableWithHasTable(string $content, string $tableName): string
    {
        // For alter table, we check IF table exists (opposite of create)
        $pattern = '/(Schema::table\s*\(\s*[\'"]' . preg_quote($tableName, '/') . '[\'"]\s*,\s*function\s*\([^)]*\)\s*\{[\s\S]*?\}\s*\)\s*;)/';
        
        $newContent = preg_replace_callback($pattern, function ($matches) use ($tableName) {
            $tableBlock = $matches[1];
            
            // Check if it's adding a column - wrap with column check too
            if (preg_match('/\$table->(\w+)\s*\(\s*[\'"](\w+)[\'"]/', $tableBlock, $colMatch)) {
                $columnName = $colMatch[2];
                $indentedBlock = preg_replace('/^/m', '    ', $tableBlock);
                return "if (Schema::hasTable('{$tableName}') && !Schema::hasColumn('{$tableName}', '{$columnName}')) {\n        {$indentedBlock}\n        }";
            }
            
            $indentedBlock = preg_replace('/^/m', '    ', $tableBlock);
            return "if (Schema::hasTable('{$tableName}')) {\n        {$indentedBlock}\n        }";
        }, $content, 1);

        return $newContent ?? $content;
    }
}
