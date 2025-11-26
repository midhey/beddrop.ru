<?php

namespace Database\Seeders;

use App\Models\ProductCategory;
use Illuminate\Database\Seeder;

class ProductCategorySeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['slug' => 'pizza', 'name' => 'Пицца', 'sort_order' => 10],
            ['slug' => 'burgers', 'name' => 'Бургеры', 'sort_order' => 20],
            ['slug' => 'wok', 'name' => 'WOK / Лапша', 'sort_order' => 30],
            ['slug' => 'sushi', 'name' => 'Суши и роллы', 'sort_order' => 40],
            ['slug' => 'drinks', 'name' => 'Напитки', 'sort_order' => 50],
            ['slug' => 'sauces', 'name' => 'Соусы', 'sort_order' => 60],
            ['slug' => 'desserts', 'name' => 'Десерты', 'sort_order' => 70],
        ];

        foreach ($items as $item) {
            ProductCategory::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'sort_order' => $item['sort_order'],
                ]
            );
        }
    }
}
