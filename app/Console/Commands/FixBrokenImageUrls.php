<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\CardDataNormalized;
use App\Models\CardDataFromSetData;

class FixBrokenImageUrls extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fix-broken-image-urls {--test-only : Only show what would be fixed without updating}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix broken image URLs that were reported from the frontend';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $testOnly = $this->option('test-only');
        
        $this->info('Starting to fix broken image URLs...');
        
        if ($testOnly) {
            $this->info("TEST MODE: Will not update any records");
        }
        
        // Read broken URLs from log file
        $logFile = storage_path('logs/broken_image_urls.json');
        
        if (!file_exists($logFile)) {
            $this->info('No broken image URLs found to fix.');
            return;
        }
        
        $brokenUrls = json_decode(file_get_contents($logFile), true);
        
        if (empty($brokenUrls)) {
            $this->info('No broken image URLs found to fix.');
            return;
        }
        
        // Filter out already fixed ones
        $unfixed = array_filter($brokenUrls, fn($item) => !($item['fixed'] ?? false));
        
        $this->info("Found " . count($unfixed) . " broken URLs to fix");
        
        $fixed = 0;
        $notFound = 0;
        
        foreach ($unfixed as $index => $brokenUrl) {
            $cardName = $brokenUrl['cardName'];
            $currentUrl = $brokenUrl['url'];
            
            $this->info("Processing: {$cardName}");
            
            // Find alternative URL
            $alternativeUrl = $this->findAlternativeImageUrl($cardName, $currentUrl);
            
            if ($alternativeUrl) {
                if (!$testOnly) {
                    // Update the database
                    $updated = CardDataNormalized::where('name', $cardName)
                        ->where('image_url_to_use', $currentUrl)
                        ->update(['image_url_to_use' => $alternativeUrl]);
                    
                    if ($updated > 0) {
                        // Mark as fixed in log
                        $brokenUrls[$index]['fixed'] = true;
                        $brokenUrls[$index]['fixed_at'] = now()->toISOString();
                        $brokenUrls[$index]['new_url'] = $alternativeUrl;
                        
                        $this->info("✓ Fixed {$cardName}: {$alternativeUrl}");
                        $fixed++;
                    } else {
                        $this->warn("Database update failed for {$cardName}");
                    }
                } else {
                    $this->info("Would fix {$cardName} with: {$alternativeUrl}");
                    $fixed++;
                }
            } else {
                $this->error("✗ No alternative found for {$cardName}");
                $notFound++;
            }
        }
        
        if (!$testOnly) {
            // Update the log file
            file_put_contents($logFile, json_encode($brokenUrls, JSON_PRETTY_PRINT));
        }
        
        $this->info("Completed! Fixed: {$fixed}, Not found: {$notFound}");
    }
    
    /**
     * Find an alternative image URL for the card
     */
    private function findAlternativeImageUrl(string $cardName, string $currentUrl): ?string
    {
        // Try to find alternative printings with non-Gatherer URLs first
        $alternativePrinting = CardDataFromSetData::where('name', $cardName)
            ->whereNotNull('image_url')
            ->where('image_url', '!=', $currentUrl)
            ->where('image_url', 'NOT LIKE', '%gatherer.wizards.com%')
            ->first();
            
        if ($alternativePrinting) {
            return $alternativePrinting->image_url;
        }
        
        // If no non-Gatherer URLs, try other Gatherer URLs
        $gathererAlternative = CardDataFromSetData::where('name', $cardName)
            ->whereNotNull('image_url')
            ->where('image_url', '!=', $currentUrl)
            ->where('image_url', 'LIKE', '%gatherer.wizards.com%')
            ->first();
            
        if ($gathererAlternative) {
            return $gathererAlternative->image_url;
        }
        
        return null;
    }
}