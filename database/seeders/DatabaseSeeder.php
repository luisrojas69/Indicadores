<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

       \App\Models\User::factory()->create([

             'name' => 'Luis',
             'last_name' => 'Rojas',
             'password' =>  '12345678',
             'email' => 'luisrojas19@gmail.com',

         ]);

        \App\Models\User::factory()->create([

             'name' => 'Vendedor',
             'last_name' => 'Prueba',
             'password' =>  '12345678',
             'email' => 'vendedor@mail.com',

         ]);

        $this->call(RolesAndPermissionsSeeder::class);
    }
}
