<?php

namespace Database\Seeders;

use App\Models\Media;
use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\ProductImage;
use App\Models\Restaurant;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        // Рестораны
        $pizzaRestaurant   = Restaurant::where('name', 'Пицца на районе')->first();
        $burgerRestaurant  = Restaurant::where('name', 'Бургерная «Двор»')->first();
        $wokRestaurant     = Restaurant::where('name', 'Лапша wok wok')->first();

        $sushiRestaurant   = Restaurant::where('name', 'Суши & Роллы')->first();
        $shaurmaRestaurant = Restaurant::where('name', 'Шаурма Street')->first();
        $testoRestaurant   = Restaurant::where('name', 'Тесто & Соус')->first();
        $pelmeniRestaurant = Restaurant::where('name', 'Пельменная #1')->first();
        $cheburekiRestaurant = Restaurant::where('name', 'Чебуречная Братцы')->first();
        $grillRestaurant   = Restaurant::where('name', 'Гриль & Мангал')->first();
        $homeRestaurant    = Restaurant::where('name', 'Домашняя Кухня')->first();

        if (! $pizzaRestaurant || ! $burgerRestaurant || ! $wokRestaurant) {
            throw new \RuntimeException('Some demo restaurants not found. Run RestaurantSeeder first.');
        }

        $catPizza    = ProductCategory::where('slug', 'pizza')->first();
        $catBurgers  = ProductCategory::where('slug', 'burgers')->first();
        $catWok      = ProductCategory::where('slug', 'wok')->first();
        $catDrinks   = ProductCategory::where('slug', 'drinks')->first();
        $catSushi    = ProductCategory::where('slug', 'sushi')->first();
        $catSauces   = ProductCategory::where('slug', 'sauces')->first();
        $catDesserts = ProductCategory::where('slug', 'desserts')->first();

        if (! $catPizza || ! $catBurgers || ! $catWok || ! $catDrinks || ! $catSushi || ! $catSauces || ! $catDesserts) {
            throw new \RuntimeException('Some product categories not found. Run ProductCategorySeeder first.');
        }

        $productsConfig = [
            // -------- Пицца на районе -------- //
            [
                'restaurant' => $pizzaRestaurant,
                'items'      => [
                    [
                        'category_id' => $catPizza->id,
                        'name'        => 'Пицца Маргарита',
                        'description' => 'Классическая пицца: томатный соус, моцарелла, базилик.',
                        'price'       => 450.00,
                        'is_active'   => true,
                        'images'      => [
                            [
                                'path'       => 'media/products/pizza/margarita_1.png',
                                'sort_order' => 0,
                                'is_cover'   => true,
                            ],
                            [
                                'path'       => 'media/products/pizza/margarita_2.png',
                                'sort_order' => 1,
                                'is_cover'   => false,
                            ],
                            [
                                'path'       => 'media/products/pizza/margarita_closeup.png',
                                'sort_order' => 2,
                                'is_cover'   => false,
                            ],
                        ],
                    ],
                    [
                        'category_id' => $catPizza->id,
                        'name'        => 'Пицца Пепперони',
                        'description' => 'Пикантная пепперони, моцарелла, томатный соус.',
                        'price'       => 520.00,
                        'is_active'   => true,
                        'images'      => [
                            [
                                'path'       => 'media/products/pizza/pepperoni_1.png',
                                'sort_order' => 0,
                                'is_cover'   => true,
                            ],
                            [
                                'path'       => 'media/products/pizza/pepperoni_slice.png',
                                'sort_order' => 1,
                                'is_cover'   => false,
                            ],
                        ],
                    ],
                    [
                        'category_id' => $catDrinks->id,
                        'name'        => 'Кола 0.5',
                        'description' => 'Охлаждённый безалкогольный напиток, 0.5 л.',
                        'price'       => 120.00,
                        'is_active'   => true,
                        'images'      => [
                            [
                                'path'       => 'media/products/drinks/cola_05_front.png',
                                'sort_order' => 0,
                                'is_cover'   => true,
                            ],
                            [
                                'path'       => 'media/products/drinks/cola_05_group.png',
                                'sort_order' => 1,
                                'is_cover'   => false,
                            ],
                        ],
                    ],
                ],
            ],

            // -------- Бургерная «Двор» -------- //
            [
                'restaurant' => $burgerRestaurant,
                'items'      => [
                    [
                        'category_id' => $catBurgers->id,
                        'name'        => 'Чизбургер классический',
                        'description' => 'Говяжья котлета, сыр чеддер, маринованные огурчики, соус.',
                        'price'       => 390.00,
                        'is_active'   => true,
                        'images'      => [
                            [
                                'path'       => 'media/products/burgers/cheeseburger_front.png',
                                'sort_order' => 0,
                                'is_cover'   => true,
                            ],
                            [
                                'path'       => 'media/products/burgers/cheeseburger_side.png',
                                'sort_order' => 1,
                                'is_cover'   => false,
                            ],
                            [
                                'path'       => 'media/products/burgers/cheeseburger_cut.png',
                                'sort_order' => 2,
                                'is_cover'   => false,
                            ],
                        ],
                    ],
                    [
                        'category_id' => $catBurgers->id,
                        'name'        => 'Двойной бургер',
                        'description' => 'Две говяжьи котлеты, сыр, лук, салат, фирменный соус.',
                        'price'       => 520.00,
                        'is_active'   => true,
                        'images'      => [
                            [
                                'path'       => 'media/products/burgers/double_burger_front.png',
                                'sort_order' => 0,
                                'is_cover'   => true,
                            ],
                            [
                                'path'       => 'media/products/burgers/double_burger_top.png',
                                'sort_order' => 1,
                                'is_cover'   => false,
                            ],
                        ],
                    ],
                ],
            ],

            // -------- Лапша wok wok -------- //
            [
                'restaurant' => $wokRestaurant,
                'items'      => [
                    [
                        'category_id' => $catWok->id,
                        'name'        => 'WOK с курицей и овощами',
                        'description' => 'Пшеничная лапша, курица, овощи, соус терияки.',
                        'price'       => 370.00,
                        'is_active'   => true,
                        'images'      => [
                            [
                                'path'       => 'media/products/wok/chicken_veg_bowl.png',
                                'sort_order' => 0,
                                'is_cover'   => true,
                            ],
                            [
                                'path'       => 'media/products/wok/chicken_veg_closeup.png',
                                'sort_order' => 1,
                                'is_cover'   => false,
                            ],
                        ],
                    ],
                    [
                        'category_id' => $catWok->id,
                        'name'        => 'WOK с говядиной',
                        'description' => 'Яичная лапша, говядина, овощи, острый соус.',
                        'price'       => 420.00,
                        'is_active'   => true,
                        'images'      => [
                            [
                                'path'       => 'media/products/wok/beef_spicy_bowl.png',
                                'sort_order' => 0,
                                'is_cover'   => true,
                            ],
                            [
                                'path'       => 'media/products/wok/beef_spicy_closeup.png',
                                'sort_order' => 1,
                                'is_cover'   => false,
                            ],
                        ],
                    ],
                ],
            ],

            // -------- Суши & Роллы -------- //
            [
                'restaurant' => $sushiRestaurant,
                'items'      => [
                    [
                        'category_id' => $catSushi->id,
                        'name'        => 'Сет «Классический»',
                        'description' => 'Ассорти роллов и суши для компании из 2–3 человек.',
                        'price'       => 890.00,
                        'is_active'   => true,
                    ],
                ],
            ],

            // -------- Шаурма Street -------- //
            [
                'restaurant' => $shaurmaRestaurant,
                'items'      => [
                    [
                        'category_id' => $catBurgers->id,
                        'name'        => 'Шаурма классическая',
                        'description' => 'Курица, свежие овощи, фирменный соус, лаваш.',
                        'price'       => 260.00,
                        'is_active'   => true,
                    ],
                ],
            ],

            // -------- Тесто & Соус -------- //
            [
                'restaurant' => $testoRestaurant,
                'items'      => [
                    [
                        'category_id' => $catPizza->id,
                        'name'        => 'Фокачча с чесночным соусом',
                        'description' => 'Итальянская лепёшка на тонком тесте, чесночный соус.',
                        'price'       => 230.00,
                        'is_active'   => true,
                    ],
                ],
            ],

            // -------- Пельменная #1 -------- //
            [
                'restaurant' => $pelmeniRestaurant,
                'items'      => [
                    [
                        'category_id' => $catSauces->id,
                        'name'        => 'Пельмени классические',
                        'description' => 'Ручной лепки, подаются со сметаной и зеленью.',
                        'price'       => 310.00,
                        'is_active'   => true,
                    ],
                ],
            ],

            // -------- Чебуречная Братцы -------- //
            [
                'restaurant' => $cheburekiRestaurant,
                'items'      => [
                    [
                        'category_id' => $catSauces->id,
                        'name'        => 'Чебурек с говядиной',
                        'description' => 'Сочный чебурек с говяжьим фаршем и луком.',
                        'price'       => 190.00,
                        'is_active'   => true,
                    ],
                ],
            ],

            // -------- Гриль & Мангал -------- //
            [
                'restaurant' => $grillRestaurant,
                'items'      => [
                    [
                        'category_id' => $catBurgers->id,
                        'name'        => 'Шашлык из свинины',
                        'description' => 'Маринованная свинина на углях, лук, лаваш.',
                        'price'       => 520.00,
                        'is_active'   => true,
                    ],
                ],
            ],

            // -------- Домашняя Кухня -------- //
            [
                'restaurant' => $homeRestaurant,
                'items'      => [
                    [
                        'category_id' => $catDesserts->id,
                        'name'        => 'Пирог домашний',
                        'description' => 'Домашний пирог дня, выбирается на месте.',
                        'price'       => 250.00,
                        'is_active'   => true,
                    ],
                ],
            ],
        ];

        foreach ($productsConfig as $group) {
            $restaurant = $group['restaurant'];

            if (! $restaurant) {
                $this->command?->warn('Restaurant not found for products group, skip.');
                continue;
            }

            foreach ($group['items'] as $item) {
                $product = Product::updateOrCreate(
                    [
                        'restaurant_id' => $restaurant->id,
                        'name'          => $item['name'],
                    ],
                    [
                        'category_id' => $item['category_id'],
                        'description' => $item['description'] ?? null,
                        'price'       => $item['price'],
                        'is_active'   => $item['is_active'] ?? true,
                    ]
                );

                if (! empty($item['images']) && is_array($item['images'])) {
                    foreach ($item['images'] as $img) {
                        /** @var \App\Models\Media|null $media */
                        $media = Media::where('path', $img['path'])->first();

                        if (! $media) {
                            $this->command?->warn("Media not found for path [{$img['path']}], skip.");
                            continue;
                        }

                        ProductImage::updateOrCreate(
                            [
                                'product_id' => $product->id,
                                'media_id'   => $media->id,
                            ],
                            [
                                'sort_order' => $img['sort_order'] ?? 0,
                                'is_cover'   => $img['is_cover'] ?? false,
                            ]
                        );
                    }
                }
            }
        }
    }
}
