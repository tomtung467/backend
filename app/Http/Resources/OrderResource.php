<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
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
            'order_number' => $this->order_number,
            'table_id' => $this->table_id,
            'status' => $this->status,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->tax_amount,
            'service_charge' => $this->service_charge,
            'discount_amount' => $this->discount_amount,
            'total_price' => $this->total_price,
            'total_amount' => $this->total_price,
            'created_by_id' => $this->created_by_id,
            'payment_requested_at' => $this->payment_requested_at,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'items' => OrderItemResource::collection($this->whenLoaded('items')),
            'payment' => new PaymentResource($this->whenLoaded('payment')),
            'coupon' => new CouponResource($this->whenLoaded('coupon')),
        ];
    }
}
