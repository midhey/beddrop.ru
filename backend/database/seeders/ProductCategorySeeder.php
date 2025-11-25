<?php

namespace Database\Seeders;

use App\Models\Address;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RestaurantSeeder extends Seeder
{
    public function run(): void
    {
        $owner = User::where('email', 'owner@mail.com')->first();
        $manager = User::where('email', 'manager@mail.com')->first();
        $staff = User::where('email', 'staff@mail.com')->first();

        if(!$owner) {
            throw new \RuntimeException('Admin user not found. Run UserSeeder first.');
        }

        $items = [
            ['name' => 'Пицца на районе', 'phone' => '+79991111111', 'prep_time_min' => 25, 'prep_time_max' => 40],
            ['name' => 'Суши & Роллы', 'phone' => '+79992222222', 'prep_time_min' => 30, 'prep_time_max' => 50],
            ['name' => 'Бургерная «Двор»', 'phone' => '+79993333333', 'prep_time_min' => 15, 'prep_time_max' => 30],
            ['name' => 'Шаурма Street', 'phone' => '+79994444444', 'prep_time_min' => 10, 'prep_time_max' => 20],
            ['name' => 'Тесто & Соус', 'phone' => '+79995555555', 'prep_time_min' => 20, 'prep_time_max' => 35],
            ['name' => 'Пельменная #1', 'phone' => '+79996666666', 'prep_time_min' => 12, 'prep_time_max' => 25],
            ['name' => 'Чебуречная Братцы', 'phone' => '+79997777777', 'prep_time_min' => 15, 'prep_time_max' => 30],
            ['name' => 'Лапша wok wok', 'phone' => '+79998888888', 'prep_time_min' => 18, 'prep_time_max' => 30],
            ['name' => 'Гриль & Мангал', 'phone' => '+79999999999', 'prep_time_min' => 25, 'prep_time_max' => 55],
            ['name' => 'Домашняя Кухня', 'phone' => '+79990000001', 'prep_time_min' => 20, 'prep_time_max' => 40],
        ];

        foreach ($items as $index => $item) {
            $slugBase = Str::slug($item['name']);
            $slug = $slugBase;
            $i = 1;

            while (Restaurant::where('slug', $slug)->exists()) {
                $slug = $slugBase . '-' . $i;
                $i++;
            }

            $address = Address::create([
                'user_id' => $owner->id,
                'label' => 'Основной адрес ресторана',
                'line1' => 'Улица Пушкина, дом ' . ($index + 1),
                'line2' => null,
                'city' => 'Москва',
                'postal_code' => '10' . str_pad((string)$index, 4, '0', STR_PAD_LEFT),
                'lat' => null,
                'lng' => null,
            ]);

            $restaurant = Restaurant::create([
                'name' => $item['name'],
                'slug' => $slug,
                'phone' => $item['phone'],
                'is_active' => true,
                'prep_time_min' => $item['prep_time_min'],
                'prep_time_max' => $item['prep_time_max'],
                'address_id' => $address->id,
                'logo_media_id' => null,
            ]);

            $attachData = [];

            $attachData[$owner->id] = ['role' => 'OWNER'];

            if($manager) {
                $attachData[$manager->id] = ['role' => 'MANAGER'];
            }

            if($staff) {
                $attachData[$staff->id] = ['role' => 'STAFF'];
            }

            $restaurant->users()->attach($attachData);
        }
    }
}
