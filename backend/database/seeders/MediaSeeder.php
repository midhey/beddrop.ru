<?php

namespace Database\Seeders;

use App\Models\Media;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Storage;

class MediaSeeder extends Seeder
{
    public function run(): void
    {
        $mediaItems = [

            // --- Логотипы ресторанов --- //
            'media/restaurants/pizza_na_rayone_logo_1.png',
            'media/restaurants/sushi_rolls_logo_1.png',
            'media/restaurants/burger_dvor_logo_1.png',
            'media/restaurants/shaurma_street_logo_1.png',
            'media/restaurants/testo_sous_logo_1.png',
            'media/restaurants/pelmeni_one_logo_1.png',
            'media/restaurants/chebureki_bratcy_logo_1.png',
            'media/restaurants/wok_wok_logo_1.png',
            'media/restaurants/grill_mangal_logo_1.png',
            'media/restaurants/home_cooking_logo_1.png',

            // --- Пицца --- //
            'media/products/pizza/margarita_1.png',
            'media/products/pizza/margarita_2.png',
            'media/products/pizza/margarita_closeup.png',

            'media/products/pizza/pepperoni_1.png',
            'media/products/pizza/pepperoni_slice.png',

            // --- Напитки --- //
            'media/products/drinks/cola_05_front.png',
            'media/products/drinks/cola_05_group.png',

            // --- Бургеры --- //
            'media/products/burgers/cheeseburger_front.png',
            'media/products/burgers/cheeseburger_side.png',
            'media/products/burgers/cheeseburger_cut.png',

            'media/products/burgers/double_burger_front.png',
            'media/products/burgers/double_burger_top.png',

            // --- Воки --- //
            'media/products/wok/chicken_veg_bowl.png',
            'media/products/wok/chicken_veg_closeup.png',

            'media/products/wok/beef_spicy_bowl.png',
            'media/products/wok/beef_spicy_closeup.png',
        ];

        foreach ($mediaItems as $path) {
            $disk = 'public';

            $exists = Storage::disk($disk)->exists($path);

            if (! $exists) {
                $this->command?->warn("[MediaSeeder] File missing on disk [$disk]: $path");
            }

            $mime = $exists ? Storage::disk($disk)->mimeType($path) : 'image/jpeg';
            $size = $exists ? Storage::disk($disk)->size($path) : 0;

            Media::updateOrCreate(
                ['path' => $path],
                [
                    'disk'       => $disk,
                    'mime'       => $mime,
                    'size_bytes' => $size,
                ]
            );
        }

        $this->command?->info("MediaSeeder completed.");
    }
}
