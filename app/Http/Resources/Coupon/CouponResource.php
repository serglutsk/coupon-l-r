<?php

declare(strict_types=1);

namespace App\Http\Resources\Coupon;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CouponResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->resource->id,
            'name' => $this->resource->name,
            'description' => $this->resource->description,
            'code' => $this->resource->code,
            'is_active' => $this->resource->is_active,
            'valid_from' => $this->resource->valid_from?->toDateString(),
            'valid_to' => $this->resource->valid_to?->toDateString(),
            'rules' => $this->resource->rules,
            'actions' => $this->resource->actions,
            'discount_amount' => $this->resource->discount_amount,
            'created_at' => $this->resource->created_at?->toISOString(),
            'updated_at' => $this->resource->updated_at?->toISOString(),
        ];
    }
}
