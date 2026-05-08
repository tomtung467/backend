<?php

namespace App\Console\Commands;

use App\Models\Food;
use App\Services\AI\MenuAiService;
use Illuminate\Console\Command;

class WarmMenuAiProfiles extends Command
{
    protected $signature = 'ai:warm-menu {--no-embedding : Only build local search text profiles}';

    protected $description = 'Build menu AI search profiles and optional Gemini embeddings.';

    public function handle(MenuAiService $menuAi): int
    {
        $withEmbedding = !$this->option('no-embedding');
        $foods = Food::query()->with(['category', 'aiProfile'])->where('is_available', true)->get();

        $bar = $this->output->createProgressBar($foods->count());
        $bar->start();

        foreach ($foods as $food) {
            $menuAi->ensureProfile($food, $withEmbedding);
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        $this->info('Menu AI profiles are ready.');

        return self::SUCCESS;
    }
}
