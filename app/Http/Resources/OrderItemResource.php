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
            'price' => $this->unit_price,
            'unit_price' => $this->unit_price,
            'total_price' => $this->total_price,
            'status' => $this->status,
            'special_notes' => $this->special_notes,
            'food' => new FoodResource($this->whenLoaded('food')),
        ];
    }
}
