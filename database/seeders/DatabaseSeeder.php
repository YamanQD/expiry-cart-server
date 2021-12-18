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

        Product::insert([
            [
                'name' => 'Tuna',
                'price' => '10',
                'expiry_date' => '15-01-2022',
                'category' => 'Canned food',
                'contact_info' => '+966123456789',
                'thirty_days_discount' => '30',
                'fifteen_days_discount' => '60',
                'user_id' => '1',
            ],
            [
                'name' => 'Bread',
                'price' => '4.99',
                'expiry_date' => '24-02-2022',
                'category' => 'Bakery & Snacks',
                'contact_info' => '+966721416779',
                'thirty_days_discount' => '50',
                'fifteen_days_discount' => '75',
                'user_id' => '2',
            ],
        ]);
    }
}
