<?php

namespace App\Services\AI;

use App\Models\Food;
use App\Models\FoodAiProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class MenuAiService
{
    public function findRelevantFoods(string $message, int $limit = 12): Collection
    {
        $foods = Food::query()
            ->with(['category', 'aiProfile'])
            ->withSum([
                'orderItems as sold_quantity' => fn ($items) => $items->where('status', '!=', 'cancelled'),
            ], 'quantity')
            ->where('is_available', true)
            ->get();

        if ($foods->isEmpty()) {
            return collect();
        }

        $queryEmbedding = $this->embed($message, 'RETRIEVAL_QUERY');
        $foods->each(fn (Food $food) => $this->ensureProfile($food, false));

        if ($queryEmbedding) {
            $foods
                ->sortByDesc(fn (Food $food) => $this->keywordScore($message, $food->aiProfile?->search_text ?: $this->buildSearchText($food)))
                ->take(6)
                ->each(fn (Food $food) => $this->ensureProfile($food, true));
        }

        return $foods
            ->map(function (Food $food) use ($message, $queryEmbedding) {
                $semanticScore = $queryEmbedding
                    ? $this->cosineSimilarity($queryEmbedding, $food->aiProfile?->embedding ?: [])
                    : 0;

                $keywordScore = $this->keywordScore($message, $food->aiProfile?->search_text ?: $this->buildSearchText($food));
                $popularityScore = min(1, ((int) ($food->sold_quantity ?? 0)) / 20) + ($food->is_popular ? 0.25 : 0);

                $food->ai_score = round(($semanticScore * 0.65) + ($keywordScore * 0.25) + ($popularityScore * 0.10), 5);

                return $food;
            })
            ->sortByDesc('ai_score')
            ->take($limit)
            ->values();
    }

    public function formatFoodContext(Collection $foods): array
    {
        return $foods->map(fn (Food $food) => [
            'id' => $food->id,
            'name' => $food->name,
            'category' => $food->category?->name,
            'description' => $food->description,
            'price' => (int) $food->price,
            'preparation_time' => $food->preparation_time,
            'calories' => $food->calories,
            'spicy_level' => $food->spicy_level,
            'allergens' => $food->allergens,
            'ingredients' => $food->ingredients,
            'nutrition' => $food->nutrition,
            'diet_tags' => $food->diet_tags,
            'taste_profile' => $food->taste_profile,
            'best_for' => $food->best_for,
            'is_popular' => (bool) $food->is_popular,
            'sold_quantity' => (int) ($food->sold_quantity ?? 0),
            'ai_score' => $food->ai_score ?? null,
        ])->values()->all();
    }

    public function ensureProfile(Food $food, bool $withEmbedding = true): FoodAiProfile
    {
        $food->loadMissing('category');
        $searchText = $this->buildSearchText($food);
        $hash = hash('sha256', $searchText);
        $profile = $food->aiProfile;

        if ($profile && $profile->content_hash === $hash && (!$withEmbedding || $profile->embedding)) {
            return $profile;
        }

        $keepExistingEmbedding = $profile && $profile->content_hash === $hash;
        $embedding = $withEmbedding ? $this->embed($searchText, 'RETRIEVAL_DOCUMENT') : ($keepExistingEmbedding ? $profile?->embedding : null);

        $profile = FoodAiProfile::updateOrCreate(
            ['food_id' => $food->id],
            [
                'search_text' => $searchText,
                'embedding' => $embedding,
                'embedding_model' => $embedding ? config('services.gemini.embedding_model') : ($keepExistingEmbedding ? $profile?->embedding_model : null),
                'content_hash' => $hash,
                'embedded_at' => $embedding ? now() : ($keepExistingEmbedding ? $profile?->embedded_at : null),
            ]
        );

        $food->setRelation('aiProfile', $profile);

        return $profile;
    }

    public function buildSearchText(Food $food): string
    {
        $parts = [
            'Ten mon: ' . $food->name,
            'Danh muc: ' . ($food->category?->name ?: 'khong ro'),
            'Mo ta: ' . ($food->description ?: 'khong co'),
            'Gia: ' . (int) $food->price . ' VND',
            'Thoi gian chuan bi: ' . ($food->preparation_time ?: 'khong ro') . ' phut',
            'Do cay: ' . (int) $food->spicy_level,
            'Calories: ' . ($food->calories ?: 'khong ro'),
            'Thanh phan: ' . $this->jsonText($food->ingredients),
            'Dinh duong: ' . $this->jsonText($food->nutrition),
            'Di ung: ' . $this->jsonText($food->allergens),
            'Nhom an uong: ' . $this->jsonText($food->diet_tags),
            'Huong vi: ' . $this->jsonText($food->taste_profile),
            'Phu hop: ' . $this->jsonText($food->best_for),
            'Pho bien: ' . ($food->is_popular ? 'co' : 'khong'),
        ];

        return implode("\n", $parts);
    }

    public function inferMetadata(Food $food): array
    {
        $food->loadMissing('category');
        $name = (string) $food->name;
        $category = (string) $food->category?->name;
        $description = Str::lower((string) $food->description);
        $isDessert = Str::contains($category, 'Dessert');
        $isDrink = Str::contains($category, ['Beverage', 'Drink']);
        $isSeafood = Str::contains($category, 'Seafood') || Str::contains($description, ['seafood', 'shrimp', 'squid', 'bass', 'fish']);
        $isVegetarian = Str::contains($category, 'Vegetarian') || Str::contains($description, ['tofu', 'vegetable', 'mushroom']);
        $isLight = Str::contains($category, ['Salad', 'Soup', 'Vegetarian', 'Beverage']) && !$isDessert;
        $isSpicy = (int) $food->spicy_level >= 2;

        $ingredients = match (true) {
            Str::contains($name, 'Chicken') => ['chicken', 'vegetables', 'herbs'],
            Str::contains($name, ['Beef', 'Steak', 'Pho']) => ['beef', 'herbs', 'spices'],
            $isSeafood => ['seafood', 'garlic', 'herbs'],
            Str::contains($name, 'Cake') => ['chocolate', 'flour', 'cream', 'sugar'],
            Str::contains($name, 'Panna') => ['cream', 'mango', 'sugar'],
            Str::contains($name, 'Coffee') => ['coffee', 'condensed milk', 'sugar'],
            Str::contains($name, 'Soda') => ['passion fruit', 'soda', 'sugar'],
            $isVegetarian => ['vegetables', 'tofu', 'mushrooms'],
            default => ['herbs', 'vegetables', 'house seasoning'],
        };

        $sugar = match (true) {
            $isDessert => Str::contains($name, 'Cake') ? 34 : 24,
            $isDrink => Str::contains($name, 'Coffee') ? 18 : 22,
            Str::contains($name, 'Pumpkin') => 7,
            Str::contains($category, ['Salad', 'Soup', 'Seafood', 'Vegetarian']) => 3,
            default => 5,
        };

        return [
            'ingredients' => $ingredients,
            'nutrition' => [
                'calories' => $food->calories,
                'sugar_g' => $sugar,
                'protein_level' => $isVegetarian || $isDessert || $isDrink ? 'medium' : 'high',
                'carb_level' => Str::contains($category, ['Noodle', 'Rice', 'Dessert']) ? 'high' : 'medium',
            ],
            'allergens' => array_values(array_filter([
                Str::contains($category, ['Noodle', 'Appetizer', 'Dessert']) ? 'gluten' : null,
                $isSeafood ? 'seafood' : null,
                ($isDessert || Str::contains($name, ['Coffee', 'Caesar'])) ? 'dairy' : null,
                Str::contains($name, 'Fried Rice') ? 'egg' : null,
            ])),
            'diet_tags' => array_values(array_filter([
                $isVegetarian ? 'vegetarian' : null,
                $isLight ? 'light' : null,
                $sugar <= 5 ? 'low_sugar' : null,
                (int) $food->spicy_level === 0 ? 'not_spicy' : null,
                !$isSpicy && !$isDrink ? 'kid_friendly' : null,
                $isSeafood ? 'seafood' : null,
            ])),
            'taste_profile' => array_values(array_filter([
                $isSpicy ? 'spicy' : null,
                $isDessert || $isDrink ? 'sweet' : null,
                Str::contains($description, 'sour') ? 'sour' : null,
                Str::contains($description, 'grilled') ? 'smoky' : null,
                $isLight ? 'fresh' : null,
                Str::contains($description, ['creamy', 'butter']) ? 'creamy' : null,
            ])),
            'best_for' => array_values(array_filter([
                (int) $food->preparation_time <= 10 ? 'quick_order' : null,
                $isLight ? 'light_meal' : null,
                (int) $food->price <= 80000 ? 'budget' : null,
                $food->is_popular ? 'popular_choice' : null,
                Str::contains($category, ['Main', 'Seafood', 'Rice']) ? 'main_meal' : null,
                $isDessert ? 'dessert' : null,
                $isDrink ? 'drink' : null,
            ])),
        ];
    }

    private function embed(string $text, string $taskType): ?array
    {
        $apiKey = config('services.gemini.key');
        $model = Str::after(config('services.gemini.embedding_model'), 'models/');

        if (!$apiKey || !$model) {
            return null;
        }

        try {
            $response = Http::timeout(15)
                ->retry(1, 250)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:embedContent?key={$apiKey}", [
                    'model' => "models/{$model}",
                    'content' => [
                        'parts' => [
                            ['text' => Str::limit($text, 8000, '')],
                        ],
                    ],
                    'taskType' => $taskType,
                    'outputDimensionality' => 768,
                ]);

            if (!$response->successful()) {
                return null;
            }

            $values = data_get($response->json(), 'embedding.values');

            return is_array($values) ? array_map('floatval', $values) : null;
        } catch (\Throwable) {
            return null;
        }
    }

    private function keywordScore(string $message, string $searchText): float
    {
        $queryTerms = $this->terms($message);
        if ($queryTerms === []) {
            return 0;
        }

        $haystack = ' ' . Str::lower($this->normalize($searchText)) . ' ';
        $matches = collect($queryTerms)
            ->filter(fn ($term) => Str::contains($haystack, ' ' . $term . ' ') || Str::contains($haystack, $term))
            ->count();

        return min(1, $matches / max(1, count($queryTerms)));
    }

    private function terms(string $text): array
    {
        $normalized = $this->normalize($text);
        $tokens = preg_split('/\s+/', Str::lower($normalized), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $stopWords = ['mon', 'nao', 'cho', 'toi', 'minh', 'co', 'khong', 'la', 'va', 'hoac', 'goi', 'y', 'an', 'u'];

        return array_values(array_unique(array_filter(
            $tokens,
            fn ($token) => mb_strlen($token) >= 2 && !in_array($token, $stopWords, true)
        )));
    }

    private function normalize(string $text): string
    {
        $text = Str::ascii($text);
        $text = preg_replace('/[^a-zA-Z0-9]+/', ' ', $text) ?: '';

        return trim($text);
    }

    private function cosineSimilarity(array $a, array $b): float
    {
        if ($a === [] || $b === [] || count($a) !== count($b)) {
            return 0;
        }

        $dot = 0;
        $normA = 0;
        $normB = 0;

        foreach ($a as $index => $value) {
            $left = (float) $value;
            $right = (float) $b[$index];
            $dot += $left * $right;
            $normA += $left * $left;
            $normB += $right * $right;
        }

        if ($normA <= 0 || $normB <= 0) {
            return 0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }

    private function jsonText(mixed $value): string
    {
        if ($value === null || $value === []) {
            return 'chua co du lieu';
        }

        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: 'chua co du lieu';
    }
}
