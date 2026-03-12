<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'product_id'   => $this->product_id,
            'product_name' => $this->product?->name,
            'quantity'     => $this->quantity,
            'unit_price'   => $this->unit_price,
            'subtotal'     => $this->unit_price * $this->quantity,
        ];
    }
}
