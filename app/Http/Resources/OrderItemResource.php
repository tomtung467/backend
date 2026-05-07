<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'order_id' => $this->order_id,
            'food_id' => $this->food_id,
            'quantity' => $this->quantity,
            'price' => $this->price,
            'food' => new FoodResource($this->whenLoaded('food')),
        ];
    }
}
