<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransactionResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'               => $this->id,
            'status'           => $this->status->value,
            'amount'           => $this->amount,
            'card_last_numbers'=> $this->card_last_numbers,
            'external_id'      => $this->external_id,
            'gateway'          => $this->whenLoaded('gateway', fn() => [
                'id'   => $this->gateway->id,
                'name' => $this->gateway->name,
            ]),
            'client'           => $this->whenLoaded('client', fn() => [
                'id'    => $this->client->id,
                'name'  => $this->client->name,
                'email' => $this->client->email,
            ]),
            'products'         => TransactionProductResource::collection($this->whenLoaded('products')),
            'created_at'       => $this->created_at?->toIso8601String(),
        ];
    }
}
