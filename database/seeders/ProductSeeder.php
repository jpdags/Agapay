<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        DB::table('products')->insert([
            ['name' => 'LPG Gas Tank', 'category' => 'LPG', 'price' => 950],
            ['name' => 'Drinking Water Jug', 'category' => 'Water', 'price' => 150],
            // Add more products here
        ]);
    }
}
