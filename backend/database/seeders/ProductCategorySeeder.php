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
            ['slug' => 'soups', 'name' => 'Супы', 'sort_order' => 45],
            ['slug' => 'salads', 'name' => 'Салаты', 'sort_order' => 48],
            ['slug' => 'main', 'name' => 'Горячие блюда', 'sort_order' => 50],
            ['slug' => 'grill', 'name' => 'Гриль и мангал', 'sort_order' => 55],
            ['slug' => 'pelmeni', 'name' => 'Пельмени и вареники', 'sort_order' => 58],
            ['slug' => 'pastries', 'name' => 'Выпечка', 'sort_order' => 60],
            ['slug' => 'drinks', 'name' => 'Напитки', 'sort_order' => 70],
            ['slug' => 'sauces', 'name' => 'Соусы', 'sort_order' => 80],
            ['slug' => 'desserts', 'name' => 'Десерты', 'sort_order' => 90],
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
