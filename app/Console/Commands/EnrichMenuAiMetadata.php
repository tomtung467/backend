<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Services\AI\MenuAiService;
use Illuminate\Console\Command;

class EnrichMenuAiMetadata extends Command
{
    protected $signature = 'ai:enrich-menu {--force : Replace existing AI metadata fields}';

    protected $description = 'Backfill menu nutrition, ingredient and recommendation tags for the AI assistant.';

    public function handle(MenuAiService $menuAi): int
    {
        $force = (bool) $this->option('force');
        $foods = Food::query()->with('category')->get();
        $updated = 0;

        foreach ($foods as $food) {
            $metadata = $menuAi->inferMetadata($food);
            $payload = [];

            foreach (['ingredients', 'nutrition', 'allergens', 'diet_tags', 'taste_profile', 'best_for'] as $field) {
                if ($force || blank($food->{$field})) {
                    $payload[$field] = $metadata[$field];
                }
            }

            if ($payload !== []) {
                $food->update($payload);
                $updated++;
            }
        }

        $this->info("AI metadata enriched for {$updated} menu items.");

        return self::SUCCESS;
    }
}
