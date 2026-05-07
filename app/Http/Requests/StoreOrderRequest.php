<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'table_id' => 'required|exists:tables,id',
            'items' => 'required|array|min:1',
            'items.*.food_id' => 'required|exists:foods,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'nullable|numeric|min:0',
            'coupon_id' => 'nullable|exists:coupons,id',
            'notes' => 'nullable|string|max:500',
            'customer_notes' => 'nullable|string|max:500',
            'special_requests' => 'nullable|string|max:500',
            'source' => 'nullable|string|max:50',
        ];
    }

    /**
     * Get custom attribute names
     */
    public function attributes(): array
    {
        return [
            'table_id' => 'Bàn',
            'items' => 'Các mục đơn hàng',
            'items.*.food_id' => 'Mã thực phẩm',
            'items.*.quantity' => 'Số lượng',
            'items.*.price' => 'Giá',
            'coupon_id' => 'Mã khuyến mãi',
            'notes' => 'Ghi chú',
        ];
    }
}
