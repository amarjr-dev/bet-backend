<?php

namespace Database\Seeders;

use App\Models\Gateway;
use Illuminate\Database\Seeder;

class GatewaySeeder extends Seeder
{
    public function run(): void
    {
        Gateway::firstOrCreate(
            ['name' => 'Gateway 1'],
            [
                'driver'    => 'Gateway1Adapter',
                'is_active' => true,
                'priority'  => 1,
                'credentials' => json_encode([
                    'url'   => env('GATEWAY1_URL', 'http://gateways-mock:3001'),
                    'email' => env('GATEWAY1_EMAIL', 'dev@betalent.tech'),
                    'token' => env('GATEWAY1_TOKEN', 'FEC9BB078BF338F464F96B48089EB498'),
                ]),
            ]
        );

        Gateway::firstOrCreate(
            ['name' => 'Gateway 2'],
            [
                'driver'    => 'Gateway2Adapter',
                'is_active' => true,
                'priority'  => 2,
                'credentials' => json_encode([
                    'url'        => env('GATEWAY2_URL', 'http://gateways-mock:3002'),
                    'auth_token' => env('GATEWAY2_AUTH_TOKEN', 'tk_f2198cc671b5289fa856'),
                    'auth_secret'=> env('GATEWAY2_AUTH_SECRET', '3d15e8ed6131446ea7e3456728b1211f'),
                ]),
            ]
        );
    }
}
