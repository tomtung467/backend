<?php

namespace App\Http\Controllers;

use App\Models\CustomerAiInteraction;
use App\Services\AI\MenuAiService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class CustomerAiController extends Controller
{
    public function __construct(private readonly MenuAiService $menuAi)
    {
    }

    public function chat(Request $request)
    {
        $validated = $request->validate([
            'message' => 'required|string|max:1000',
            'table_id' => 'nullable|integer|exists:tables,id',
            'session_id' => 'nullable|string|max:100',
        ]);

        $apiKey = config('services.gemini.key');
        if (!$apiKey) {
            return response()->json([
                'message' => 'Gemini API key is not configured.',
            ], 503);
        }

        $relevantFoods = $this->menuAi->findRelevantFoods($validated['message']);
        $foods = $this->menuAi->formatFoodContext($relevantFoods);

        $prompt = [
            'Bạn là trợ lý tư vấn món ăn cho khách của Nhà Hàng Phương Nam.',
            'Chỉ trả lời dựa trên dữ liệu món ăn JSON được cung cấp. Không bịa thông tin dinh dưỡng, đường, dị ứng hay thành phần nếu JSON không có.',
            'Nếu dữ liệu không có thông tin như đường, dinh dưỡng hoặc dị ứng, hãy nói rõ là chưa có dữ liệu đầy đủ, sau đó gợi ý thận trọng dựa trên tên, mô tả, calories, độ cay và danh mục.',
            'Trả lời bằng tiếng Việt có dấu, ngắn gọn, thân thiện, dễ hiểu. Ưu tiên 2-4 món phù hợp nhất.',
            'Khi hỏi món phổ biến nhất, dựa vào sold_quantity và is_popular.',
            'Khi hỏi món ít đường, chỉ xác nhận ít đường nếu nutrition.sugar_g có dữ liệu. Nếu không có sugar_g, hãy nói chưa có dữ liệu đường.',
            'Khi gợi ý món, nếu có thể hãy kèm giá và lý do ngắn.',
            'Dữ liệu món ăn JSON:',
            json_encode($foods, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'Câu hỏi của khách:',
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
                    'INVALID_ARGUMENT' => 'Gemini API key không hợp lệ. Vui lòng kiểm tra lại key trong backend/.env.',
                    'NOT_FOUND' => "Model Gemini '{$model}' không khả dụng. Hãy đổi GEMINI_MODEL sang gemini-2.5-pro hoặc gemini-2.5-flash.",
                    'PERMISSION_DENIED' => 'Gemini API key chưa có quyền dùng Generative Language API.',
                    'RESOURCE_EXHAUSTED' => 'Gemini đã vượt giới hạn quota hoặc rate limit.',
                    default => 'Không thể kết nối trợ lý AI lúc này.',
                };

                return response()->json([
                    'message' => $message,
                    'detail' => config('app.debug') ? $errorMessage : null,
                ], 502);
            }

            $reply = data_get($response->json(), 'candidates.0.content.parts.0.text');
            $reply = $reply ?: 'Mình chưa có đủ dữ liệu để trả lời câu này.';

            $this->logInteraction($request, $validated, $reply, $relevantFoods->pluck('id')->all(), [
                'model' => $model,
                'retrieval' => 'embedding_with_keyword_fallback',
            ]);

            return response()->json([
                'reply' => $reply,
                'recommendations' => $relevantFoods->take(4)->map(fn ($food) => [
                    'id' => $food->id,
                    'name' => $food->name,
                    'price' => (int) $food->price,
                    'description' => $food->description,
                    'image_url' => $food->image_url,
                    'ai_score' => $food->ai_score ?? null,
                ])->values(),
            ]);
        } catch (\Throwable $e) {
            report($e);

            $reply = 'Trợ lý AI đang bận, vui lòng thử lại sau.';
            $this->logInteraction($request, $validated, $reply, $relevantFoods->pluck('id')->all(), [
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $reply,
            ], 502);
        }
    }

    public function trackEvent(Request $request)
    {
        $validated = $request->validate([
            'event_type' => 'required|string|max:50',
            'message' => 'nullable|string|max:1000',
            'reply' => 'nullable|string|max:4000',
            'table_id' => 'nullable|integer|exists:tables,id',
            'session_id' => 'nullable|string|max:100',
            'candidate_food_ids' => 'nullable|array',
            'candidate_food_ids.*' => 'integer|exists:foods,id',
            'selected_food_ids' => 'nullable|array',
            'selected_food_ids.*' => 'integer|exists:foods,id',
            'metadata' => 'nullable|array',
        ]);

        CustomerAiInteraction::create([
            'user_id' => $request->user()?->id,
            'table_id' => $validated['table_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
            'event_type' => $validated['event_type'],
            'message' => $validated['message'] ?? null,
            'reply' => $validated['reply'] ?? null,
            'candidate_food_ids' => $validated['candidate_food_ids'] ?? null,
            'selected_food_ids' => $validated['selected_food_ids'] ?? null,
            'metadata' => $validated['metadata'] ?? null,
        ]);

        return response()->json(['message' => 'AI event tracked']);
    }

    private function logInteraction(Request $request, array $validated, string $reply, array $candidateFoodIds, array $metadata): void
    {
        CustomerAiInteraction::create([
            'user_id' => $request->user()?->id,
            'table_id' => $validated['table_id'] ?? null,
            'session_id' => $validated['session_id'] ?? null,
            'event_type' => 'chat',
            'message' => $validated['message'],
            'reply' => $reply,
            'candidate_food_ids' => $candidateFoodIds,
            'metadata' => $metadata,
        ]);
    }
}
