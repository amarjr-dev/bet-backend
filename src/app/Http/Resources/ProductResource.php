<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'amount'     => $this->amount,         // valor em centavos
            'amount_brl' => number_format($this->amount / 100, 2, ',', '.'), // exemplo: R$ 29,90
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
