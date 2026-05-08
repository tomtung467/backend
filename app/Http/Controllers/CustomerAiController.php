<?php

namespace App\Http\Controllers;

use App\Models\Food;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CustomerAiController extends Controller
{
    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
        ]);

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json([
                'message' => 'Gemini API key is not configured.',
            ], 503);
        }

        $foods = Food::query()
            ->with('category')
            ->withSum([
                'orderItems as sold_quantity' => fn ($items) => $items->where('status', '!=', 'cancelled'),
            ], 'quantity')
            ->where('is_available', true)
            ->orderByDesc('is_popular')
            ->orderByDesc('sold_quantity')
            ->limit(80)
            ->get()
            ->map(fn ($food) => [
                'name' => $food->name,
                'category' => $food->category?->name,
                'description' => $food->description,
                'price' => (int) $food->price,
                'calories' => $food->calories,
                'spicy_level' => $food->spicy_level,
                'allergens' => $food->allergens,
                'is_popular' => (bool) $food->is_popular,
                'sold_quantity' => (int) ($food->sold_quantity ?? 0),
            ])
            ->values();

        $prompt = [
            'Ban la tro ly tu van mon an cho khach cua Nha Hang Phuong Nam.',
            'Chi tra loi dua tren du lieu mon an duoc cung cap. Neu du lieu khong co thong tin nhu duong, dinh duong hoac di ung, hay noi ro la chua co du lieu day du va dua ra goi y than trong tu ten/mo ta mon.',
            'Tra loi bang tieng Viet ngan gon, de hieu. Co the goi y 2-4 mon phu hop.',
            'Khi hoi mon pho bien nhat, dua vao sold_quantity va is_popular.',
            'Du lieu mon an JSON:',
            $foods->toJson(JSON_UNESCAPED_UNICODE),
            'Cau hoi cua khach:',
            $validated['message'],
        ];

        try {
            $model = Str::after(config('services.gemini.model'), 'models/');
            $response = Http::timeout(25)
                ->retry(1, 300)
                ->post("https://generativelanguage.googleapis.com/v1beta/models/{$model}:generateContent?key={$apiKey}", [
                    'contents' => [
                        [
                            'role' => 'user',
                            'parts' => [
                                ['text' => implode("\n\n", $prompt)],
                            ],
                        ],
                    ],
                    'generationConfig' => [
                        'temperature' => 0.35,
                        'maxOutputTokens' => 600,
                    ],
                ]);

            if (!$response->successful()) {
                $errorStatus = data_get($response->json(), 'error.status');
                $errorMessage = data_get($response->json(), 'error.message');
                $message = match ($errorStatus) {
                    'INVALID_ARGUMENT' => 'Gemini API key khong hop le. Vui long kiem tra lai key trong backend/.env.',
                    'NOT_FOUND' => "Model Gemini '{$model}' khong kha dung. Hay doi GEMINI_MODEL sang gemini-2.5-pro hoac gemini-2.5-flash.",
                    'PERMISSION_DENIED' => 'Gemini API key chua co quyen dung Generative Language API.',
                    'RESOURCE_EXHAUSTED' => 'Gemini da vuot gioi han quota hoac rate limit.',
                    default => 'Khong the ket noi tro ly AI luc nay.',
                };

                return response()->json([
                    'message' => $message,
                    'detail' => config('app.debug') ? $errorMessage : null,
                ], 502);
            }

            $reply = data_get($response->json(), 'candidates.0.content.parts.0.text');

            return response()->json([
                'reply' => $reply ?: 'Minh chua co du du lieu de tra loi cau nay.',
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Tro ly AI dang ban, vui long thu lai sau.',
            ], 502);
        }
    }
}
