<?php

namespace Database\Seeders;

use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            ['name' => 'Mouse Óptico USB',                'amount' => 2590],   // R$ 25,90
            ['name' => 'Teclado Mecânico RGB',            'amount' => 18990],  // R$ 189,90
            ['name' => 'Monitor LED 24 Polegadas',        'amount' => 89990],  // R$ 899,90
            ['name' => 'HD Externo 1TB',                  'amount' => 32990],  // R$ 329,90
            ['name' => 'SSD NVMe 512GB',                  'amount' => 27990],  // R$ 279,90
            ['name' => 'Webcam Full HD',                  'amount' => 14990],  // R$ 149,90
            ['name' => 'Headset Gamer',                   'amount' => 11990],  // R$ 119,90
            ['name' => 'Cadeira Ergonômica de Escritório','amount' => 74990],  // R$ 749,90
            ['name' => 'Hub USB 3.0 4 Portas',            'amount' => 4590],   // R$ 45,90
            ['name' => 'Mousepad Gamer XL',               'amount' => 3990],   // R$ 39,90
            ['name' => 'Fonte ATX 650W',                  'amount' => 35990],  // R$ 359,90
            ['name' => 'Placa de Vídeo 8GB',              'amount' => 189990], // R$ 1.899,90
        ];

        foreach ($products as $product) {
            Product::firstOrCreate(['name' => $product['name']], $product);
        }
    }
}
