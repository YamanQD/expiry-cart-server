<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use App\Models\User;
use App\Models\Product;

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
                'name' => 'Medicines & Personal Care'
            ],
        ]);

        User::insert([
            [
                'name' => 'Yaman',
                'email' => 'yaman@gmail.com',
                'password' => bcrypt('123456'),
            ],
            [
                'name' => 'Ahmad',
                'email' => 'ahmad@gmail.com',
                'password' => bcrypt('123456'),
            ]
        ]);
    }
}
