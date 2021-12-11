<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        Category::insert([
            [
                'name' => 'Canned food'
            ],
            [
                'name' => 'Dairy & Eggs'
            ],
            [
                'name' => 'Fruits & vegetables'
            ],
            [
                'name' => 'Meat, Fish & Poultry'
            ],
            [
                'name' => 'Beverages'
            ],
            [
                'name' => 'Bakery & Snacks'
            ],
            [
                'name' => 'Others'
            ],
        ]);
    }
}
