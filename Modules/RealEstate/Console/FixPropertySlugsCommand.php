<?php

declare(strict_types=1);

namespace Modules\RealEstate\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Modules\RealEstate\Entities\Property;
use Modules\RealEstate\Entities\Compound;

/**
 * Fix improperly formatted slugs (spaces, uppercase) in properties and compounds.
 * 
 * Usage: php artisan realestate:fix-slugs
 */
class FixPropertySlugsCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'realestate:fix-slugs
                            {--dry-run : Preview changes without updating database}
                            {--type=all : Type to fix: properties, compounds, or all}';

    /**
     * The console command description.
     */
    protected $description = 'Fix improperly formatted slugs (spaces, uppercase) in properties and compounds';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $type = $this->option('type');

        $this->info('🔧 Starting slug fix process...');
        
        if ($dryRun) {
            $this->warn('⚠️  DRY RUN MODE - No changes will be saved');
        }

        $fixedCount = 0;

        // Fix Properties
        if (in_array($type, ['all', 'properties'])) {
            $this->info("\n📦 Processing Properties...");
            $fixedCount += $this->fixPropertiesSlug($dryRun);
        }

        // Fix Compounds
        if (in_array($type, ['all', 'compounds'])) {
            $this->info("\n🏢 Processing Compounds...");
            $fixedCount += $this->fixCompoundsSlugs($dryRun);
        }

        $this->newLine();
        $this->info("✅ Complete! Fixed {$fixedCount} slugs" . ($dryRun ? ' (preview only)' : ''));

        return Command::SUCCESS;
    }

    /**
     * Fix property slugs.
     */
    protected function fixPropertiesSlug(bool $dryRun): int
    {
        $properties = Property::whereRaw("slug LIKE '% %' OR slug != LOWER(slug)")->get();
        
        if ($properties->isEmpty()) {
            $this->info('  ✓ All property slugs are already correct!');
            return 0;
        }

        $this->info("  Found {$properties->count()} properties with incorrect slugs");
        $fixed = 0;

        foreach ($properties as $property) {
            $oldSlug = $property->slug;
            $newSlug = Str::slug($property->title);

            // Ensure unique slug
            $counter = 1;
            $baseSlug = $newSlug;
            while (Property::where('slug', $newSlug)->where('id', '!=', $property->id)->exists()) {
                $newSlug = $baseSlug . '-' . $counter++;
            }

            if ($oldSlug !== $newSlug) {
                if ($dryRun) {
                    $this->line("  [Preview] Property #{$property->id}: '{$oldSlug}' → '{$newSlug}'");
                } else {
                    $property->slug = $newSlug;
                    $property->save();
                    $this->line("  ✓ Fixed Property #{$property->id}: '{$oldSlug}' → '{$newSlug}'");
                }
                $fixed++;
            }
        }

        return $fixed;
    }

    /**
     * Fix compound slugs.
     */
    protected function fixCompoundsSlugs(bool $dryRun): int
    {
        $compounds = Compound::whereRaw("slug LIKE '% %' OR slug != LOWER(slug)")->get();
        
        if ($compounds->isEmpty()) {
            $this->info('  ✓ All compound slugs are already correct!');
            return 0;
        }

        $this->info("  Found {$compounds->count()} compounds with incorrect slugs");
        $fixed = 0;

        foreach ($compounds as $compound) {
            $oldSlug = $compound->slug;
            $newSlug = Str::slug($compound->title);

            // Ensure unique slug
            $counter = 1;
            $baseSlug = $newSlug;
            while (Compound::where('slug', $newSlug)->where('id', '!=', $compound->id)->exists()) {
                $newSlug = $baseSlug . '-' . $counter++;
            }

            if ($oldSlug !== $newSlug) {
                if ($dryRun) {
                    $this->line("  [Preview] Compound #{$compound->id}: '{$oldSlug}' → '{$newSlug}'");
                } else {
                    $compound->slug = $newSlug;
                    $compound->save();
                    $this->line("  ✓ Fixed Compound #{$compound->id}: '{$oldSlug}' → '{$newSlug}'");
                }
                $fixed++;
            }
        }

        return $fixed;
    }
}
