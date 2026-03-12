<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class GatewayResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'        => $this->id,
            'name'      => $this->name,
            'driver'    => $this->driver,
            'is_active' => $this->is_active,
            'priority'  => $this->priority,
            // credenciais nunca são expostas na resposta
        ];
    }
}
