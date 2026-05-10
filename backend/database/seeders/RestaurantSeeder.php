<?php

namespace Database\Seeders;

use App\Enums\RestaurantStaffRole;
use App\Models\Address;
use App\Models\Media;
use App\Models\Restaurant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RestaurantSeeder extends Seeder
{
    private const LEGACY_RESTAURANT_NAMES = [
        'Пицца на районе',
        'Суши & Роллы',
        'Бургерная «Двор»',
        'Шаурма Street',
        'Тесто & Соус',
        'Пельменная #1',
        'Чебуречная Братцы',
        'Лапша wok wok',
        'Гриль & Мангал',
        'Домашняя Кухня',
    ];

    public function run(): void
    {
        $owner = User::where('email', 'owner@mail.com')->first();
        $manager = User::where('email', 'manager@mail.com')->first();
        $staff = User::where('email', 'staff@mail.com')->first();

        if (! $owner) {
            throw new \RuntimeException('Owner user not found. Run UserSeeder first.');
        }

        $items = $this->restaurants();
        $seedSlugs = array_column($items, 'slug');

        $this->deleteDemoRestaurants(
            Restaurant::whereIn('name', self::LEGACY_RESTAURANT_NAMES)->get()
        );

        $this->deleteDemoRestaurants(
            Restaurant::whereIn('slug', $seedSlugs)->get()
        );

        foreach ($items as $item) {
            $this->deleteDemoRestaurants(
                Restaurant::where('name', $item['name'])
                    ->where('slug', '!=', $item['slug'])
                    ->get()
            );

            $this->deleteDemoRestaurants(
                Restaurant::where('slug', 'like', $item['slug'] . '-%')->get()
            );
        }

        foreach ($items as $item) {
            $addressPayload = $this->addressPayload($owner, $item['address']);
            $restaurant = Restaurant::where('slug', $item['slug'])->first();

            if ($restaurant?->address) {
                $restaurant->address->update($addressPayload);
                $address = $restaurant->address;
            } else {
                $address = Address::updateOrCreate(
                    ['fias_id' => $item['address']['fias_id']],
                    $addressPayload
                );
            }

            $media = Media::where('path', $item['logo'])->first();

            if (! $media) {
                $this->command?->warn(
                    "Logo media not found for [{$item['name']}] path [{$item['logo']}]"
                );
            }

            $restaurant = Restaurant::updateOrCreate(
                ['slug' => $item['slug']],
                [
                    'name' => $item['name'],
                    'description' => $item['description'],
                    'phone' => $item['phone'],
                    'is_active' => true,
                    'prep_time_min' => $item['prep_time_min'],
                    'prep_time_max' => $item['prep_time_max'],
                    'address_id' => $address->id,
                    'logo_media_id' => $media?->id,
                ]
            );

            $attachData = [
                $owner->id => ['role' => RestaurantStaffRole::OWNER->value],
            ];

            if ($manager) {
                $attachData[$manager->id] = ['role' => RestaurantStaffRole::MANAGER->value];
            }

            if ($staff) {
                $attachData[$staff->id] = ['role' => RestaurantStaffRole::STAFF->value];
            }

            $restaurant->users()->sync($attachData);
        }
    }

    private function addressPayload(User $owner, array $data): array
    {
        $line1 = $data['line1']
            ?? trim(implode(', ', array_filter([
                $data['street_with_type'] ?? $data['settlement_with_type'] ?? null,
                trim(($data['house_type'] ?? '') . ' ' . ($data['house'] ?? '')),
            ])));

        return array_merge([
            'user_id' => $owner->id,
            'label' => 'Основной адрес ресторана',
            'line1' => $line1,
            'line2' => null,
            'city' => 'Великий Новгород',
            'country' => 'Россия',
            'country_iso_code' => 'RU',
            'federal_district' => 'Северо-Западный',
            'region_fias_id' => 'e5a84b81-8ea1-49e3-b3c4-0528651be129',
            'region_kladr_id' => '5300000000000',
            'region_iso_code' => 'RU-NGR',
            'region_with_type' => 'Новгородская обл',
            'region_type' => 'обл',
            'region_type_full' => 'область',
            'region' => 'Новгородская',
            'city_fias_id' => '8d0a05bf-3b8a-43e9-ac26-7ce61d7c4560',
            'city_kladr_id' => '5300000100000',
            'city_with_type' => 'г Великий Новгород',
            'city_type' => 'г',
            'city_type_full' => 'город',
            'geo_source' => 'seed_dadata_suggestions',
            'geocoded_at' => Carbon::parse('2026-05-11 00:00:00'),
        ], $data, [
            'raw_dadata_json' => $data,
        ]);
    }

    private function deleteDemoRestaurants(iterable $restaurants): void
    {
        $restaurants = collect($restaurants);

        if ($restaurants->isEmpty()) {
            return;
        }

        $restaurantIds = $restaurants->pluck('id');
        $addressIds = $restaurants->pluck('address_id')->filter();

        $cartIds = DB::table('carts')
            ->whereIn('restaurant_id', $restaurantIds)
            ->pluck('id');

        if ($cartIds->isNotEmpty()) {
            DB::table('cart_items')->whereIn('cart_id', $cartIds)->delete();
            DB::table('carts')->whereIn('id', $cartIds)->delete();
        }

        $orderIds = DB::table('orders')
            ->whereIn('restaurant_id', $restaurantIds)
            ->pluck('id');

        if ($orderIds->isNotEmpty()) {
            DB::table('order_route_segments')->whereIn('order_id', $orderIds)->delete();
            DB::table('order_events')->whereIn('order_id', $orderIds)->delete();
            DB::table('order_items')->whereIn('order_id', $orderIds)->delete();
            DB::table('orders')->whereIn('id', $orderIds)->delete();
        }

        DB::table('restaurant_user')->whereIn('restaurant_id', $restaurantIds)->delete();
        DB::table('restaurant_staff_invites')->whereIn('restaurant_id', $restaurantIds)->delete();
        Restaurant::whereIn('id', $restaurantIds)->delete();

        foreach ($restaurants as $restaurant) {
            $restaurant->unsetRelation('address');
        }

        foreach ($addressIds->unique() as $addressId) {
            $isRestaurantAddress = Restaurant::where('address_id', $addressId)->exists();
            $isDeliveryAddress = DB::table('orders')
                ->where('delivery_address_id', $addressId)
                ->exists();

            if (! $isRestaurantAddress && ! $isDeliveryAddress) {
                Address::whereKey($addressId)->delete();
            }
        }
    }

    private function restaurants(): array
    {
        return [
            [
                'name' => 'Новгородский Дворик',
                'slug' => 'novgorodskii-dvorik',
                'description' => 'Традиционная русская кухня в сердце старого города. Домашние обеды и уютная атмосфера.',
                'phone' => '+79111000001',
                'prep_time_min' => 25,
                'prep_time_max' => 45,
                'logo' => 'media/restaurants/home_cooking_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, ул Большая Московская, д 10',
                    'unrestricted_value' => '173000, Новгородская обл, г Великий Новгород, ул Большая Московская, д 10',
                    'postal_code' => '173000',
                    'street_fias_id' => '0824cb61-8492-46f5-8c27-ab7d82da2c01',
                    'street_kladr_id' => '53000001000002300',
                    'street_with_type' => 'ул Большая Московская',
                    'street_type' => 'ул',
                    'street_type_full' => 'улица',
                    'street' => 'Большая Московская',
                    'house_fias_id' => '83b89a5d-8c16-477e-82d0-d423b455ca06',
                    'house_kladr_id' => '5300000100000230009',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '10',
                    'lat' => 58.5176735,
                    'lng' => 31.2866066,
                    'fias_id' => '83b89a5d-8c16-477e-82d0-d423b455ca06',
                    'kladr_id' => '5300000100000230009',
                    'qc_geo' => 0,
                ],
            ],
            [
                'name' => 'Ильмень-Суши',
                'slug' => 'ilmen-sushi',
                'description' => 'Свежие роллы и суши с доставкой по всему городу. Качество, проверенное временем.',
                'phone' => '+79211000002',
                'prep_time_min' => 30,
                'prep_time_max' => 55,
                'logo' => 'media/restaurants/sushi_rolls_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, ул Псковская, д 12',
                    'unrestricted_value' => '173015, Новгородская обл, г Великий Новгород, ул Псковская, д 12',
                    'postal_code' => '173015',
                    'street_fias_id' => '7549b60a-171e-436f-b6b9-54d3a16b11dc',
                    'street_kladr_id' => '53000001000018500',
                    'street_with_type' => 'ул Псковская',
                    'street_type' => 'ул',
                    'street_type_full' => 'улица',
                    'street' => 'Псковская',
                    'house_fias_id' => '7a8e1d04-16e1-4394-9755-f1593247bc76',
                    'house_kladr_id' => '5300000100001850145',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '12',
                    'lat' => 58.5218361,
                    'lng' => 31.2542666,
                    'fias_id' => '7a8e1d04-16e1-4394-9755-f1593247bc76',
                    'kladr_id' => '5300000100001850145',
                    'qc_geo' => 0,
                ],
            ],
            [
                'name' => 'Ганзейский Бургер',
                'slug' => 'ganzeyskiy-burger',
                'description' => 'Сочные бургеры из местной говядины на живом огне. Вкус настоящей Ганзы.',
                'phone' => '+79511000003',
                'prep_time_min' => 15,
                'prep_time_max' => 30,
                'logo' => 'media/restaurants/burger_dvor_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, ул Фёдоровский Ручей, д 2/13',
                    'unrestricted_value' => '173000, Новгородская обл, г Великий Новгород, ул Фёдоровский Ручей, д 2/13',
                    'postal_code' => '173000',
                    'street_fias_id' => 'a542c9c0-f66a-4fd0-8685-cf7f51b199b4',
                    'street_kladr_id' => '53000001000024400',
                    'street_with_type' => 'ул Фёдоровский Ручей',
                    'street_type' => 'ул',
                    'street_type_full' => 'улица',
                    'street' => 'Фёдоровский Ручей',
                    'house_fias_id' => '2edc30ec-4402-4cc4-be62-db576efe94bc',
                    'house_kladr_id' => '5300000100002440020',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '2/13',
                    'lat' => 58.522895,
                    'lng' => 31.285114,
                    'fias_id' => '2edc30ec-4402-4cc4-be62-db576efe94bc',
                    'kladr_id' => '5300000100002440020',
                    'qc_geo' => 0,
                ],
            ],
            [
                'name' => 'Детинец Гриль',
                'slug' => 'detinets-grill',
                'description' => 'Ароматный шашлык и блюда на мангале у стен Кремля. Традиции новгородского гостеприимства.',
                'phone' => '+79021000004',
                'prep_time_min' => 20,
                'prep_time_max' => 40,
                'logo' => 'media/restaurants/grill_mangal_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, тер Кремль, д 3',
                    'unrestricted_value' => '173007, Новгородская обл, г Великий Новгород, тер Кремль, д 3',
                    'line1' => 'тер Кремль, д 3',
                    'postal_code' => '173007',
                    'settlement_fias_id' => '6e884820-833c-469a-b4e3-d6d0e7d76fc3',
                    'settlement_kladr_id' => '53000001000027600',
                    'settlement_with_type' => 'тер Кремль',
                    'settlement_type' => 'тер',
                    'settlement_type_full' => 'территория',
                    'settlement' => 'Кремль',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '3',
                    'lat' => 58.5202106,
                    'lng' => 31.2739538,
                    'fias_id' => '6e884820-833c-469a-b4e3-d6d0e7d76fc3',
                    'kladr_id' => '53000001000027600',
                    'qc_geo' => 0,
                ],
            ],
            [
                'name' => 'Волховская Трапеза',
                'slug' => 'volkhovskaya-trapeza',
                'description' => 'Домашние обеды и свежая выпечка каждый день. Готовим с любовью как для своих.',
                'phone' => '+79111000005',
                'prep_time_min' => 20,
                'prep_time_max' => 35,
                'logo' => 'media/restaurants/testo_sous_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, ул Кооперативная, д 4',
                    'unrestricted_value' => '173003, Новгородская обл, г Великий Новгород, ул Кооперативная, д 4',
                    'postal_code' => '173003',
                    'street_fias_id' => '30e2a61e-9949-4f70-979d-1138e014ac61',
                    'street_kladr_id' => '53000001000010100',
                    'street_with_type' => 'ул Кооперативная',
                    'street_type' => 'ул',
                    'street_type_full' => 'улица',
                    'street' => 'Кооперативная',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '4',
                    'lat' => 58.5343103,
                    'lng' => 31.2644914,
                    'fias_id' => '30e2a61e-9949-4f70-979d-1138e014ac61',
                    'kladr_id' => '53000001000010100',
                    'qc_geo' => 2,
                ],
            ],
            [
                'name' => 'Софийская Пицца',
                'slug' => 'sofiyskaya-pitstsa',
                'description' => 'Пицца на тонком тесте с авторскими соусами и только свежими ингредиентами.',
                'phone' => '+79601000006',
                'prep_time_min' => 25,
                'prep_time_max' => 40,
                'logo' => 'media/restaurants/pizza_na_rayone_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, пр-кт Мира, д 30',
                    'unrestricted_value' => '173025, Новгородская обл, г Великий Новгород, пр-кт Мира, д 30',
                    'postal_code' => '173025',
                    'street_fias_id' => '3ecf2c06-a6af-4d8f-8411-84dd4edd62dd',
                    'street_kladr_id' => '53000001000013400',
                    'street_with_type' => 'пр-кт Мира',
                    'street_type' => 'пр-кт',
                    'street_type_full' => 'проспект',
                    'street' => 'Мира',
                    'house_fias_id' => '6bda36fd-f97d-4398-88b0-8a6621da169f',
                    'house_kladr_id' => '5300000100001340022',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '30',
                    'lat' => 58.533307,
                    'lng' => 31.230335,
                    'fias_id' => '6bda36fd-f97d-4398-88b0-8a6621da169f',
                    'kladr_id' => '5300000100001340022',
                    'qc_geo' => 1,
                ],
            ],
            [
                'name' => 'Лапша Садко',
                'slug' => 'lapsha-sadko',
                'description' => 'Паназиатская кухня: вок, рамен и экзотические закуски для истинных гурманов.',
                'phone' => '+79211000007',
                'prep_time_min' => 15,
                'prep_time_max' => 25,
                'logo' => 'media/restaurants/wok_wok_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, ул Большая Санкт-Петербургская, д 28',
                    'unrestricted_value' => '173003, Новгородская обл, г Великий Новгород, ул Большая Санкт-Петербургская, д 28',
                    'postal_code' => '173003',
                    'street_fias_id' => 'b0ed0fed-f356-4fc9-9d7b-8b0ae4177dd8',
                    'street_kladr_id' => '53000001000002400',
                    'street_with_type' => 'ул Большая Санкт-Петербургская',
                    'street_type' => 'ул',
                    'street_type_full' => 'улица',
                    'street' => 'Большая Санкт-Петербургская',
                    'house_fias_id' => '0b7b4ef9-80f8-458d-9b65-c3334d31eade',
                    'house_kladr_id' => '5300000100000240078',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '28',
                    'lat' => 58.537004,
                    'lng' => 31.266151,
                    'fias_id' => '0b7b4ef9-80f8-458d-9b65-c3334d31eade',
                    'kladr_id' => '5300000100000240078',
                    'qc_geo' => 0,
                ],
            ],
            [
                'name' => 'Витязь Кебаб',
                'slug' => 'vityaz-kebab',
                'description' => 'Лучшая шаурма и кебаб в Великом Новгороде. Быстро, вкусно и сытно.',
                'phone' => '+79531000008',
                'prep_time_min' => 10,
                'prep_time_max' => 20,
                'logo' => 'media/restaurants/shaurma_street_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, ул Державина, д 13',
                    'unrestricted_value' => '173017, Новгородская обл, г Великий Новгород, ул Державина, д 13',
                    'postal_code' => '173017',
                    'street_fias_id' => '5c54e80c-0ed0-4764-b0ee-a337ff3849b3',
                    'street_kladr_id' => '53000001000006000',
                    'street_with_type' => 'ул Державина',
                    'street_type' => 'ул',
                    'street_type_full' => 'улица',
                    'street' => 'Державина',
                    'house_fias_id' => '4babc7e2-dc64-4928-81f9-d3e8ab443c9b',
                    'house_kladr_id' => '5300000100000600008',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '13',
                    'lat' => 58.5437657,
                    'lng' => 31.3152196,
                    'fias_id' => '4babc7e2-dc64-4928-81f9-d3e8ab443c9b',
                    'kladr_id' => '5300000100000600008',
                    'qc_geo' => 0,
                ],
            ],
            [
                'name' => 'Пряничный Домик',
                'slug' => 'pryanichnyy-domik',
                'description' => 'Семейный ресторан с уютной атмосферой и большим выбором десертов.',
                'phone' => '+79081000009',
                'prep_time_min' => 20,
                'prep_time_max' => 50,
                'logo' => 'media/restaurants/chebureki_bratcy_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, наб Александра Невского, д 22',
                    'unrestricted_value' => '173000, Новгородская обл, г Великий Новгород, наб Александра Невского, д 22',
                    'postal_code' => '173000',
                    'street_fias_id' => '6550ee5e-60ac-4e68-ad23-5282aca407e4',
                    'street_kladr_id' => '53000001000000100',
                    'street_with_type' => 'наб Александра Невского',
                    'street_type' => 'наб',
                    'street_type_full' => 'набережная',
                    'street' => 'Александра Невского',
                    'house_fias_id' => 'e8c02962-27e5-4386-95b5-1e1a2e841a35',
                    'house_kladr_id' => '5300000100000010002',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '22',
                    'lat' => 58.5210728,
                    'lng' => 31.2838091,
                    'fias_id' => 'e8c02962-27e5-4386-95b5-1e1a2e841a35',
                    'kladr_id' => '5300000100000010002',
                    'qc_geo' => 0,
                ],
            ],
            [
                'name' => 'Пельменный Мастер',
                'slug' => 'pelmennyy-master',
                'description' => 'Ручная лепка, натуральное мясо и секретные специи. Вкус как в детстве.',
                'phone' => '+79111000010',
                'prep_time_min' => 15,
                'prep_time_max' => 30,
                'logo' => 'media/restaurants/pelmeni_one_logo_1.png',
                'address' => [
                    'value' => 'г Великий Новгород, ул Ломоносова, д 15',
                    'unrestricted_value' => '173016, Новгородская обл, г Великий Новгород, ул Ломоносова, д 15',
                    'postal_code' => '173016',
                    'street_fias_id' => 'c00b5096-0600-4879-b259-4af64c1ce0aa',
                    'street_kladr_id' => '53000001000012000',
                    'street_with_type' => 'ул Ломоносова',
                    'street_type' => 'ул',
                    'street_type_full' => 'улица',
                    'street' => 'Ломоносова',
                    'house_fias_id' => '048c928d-811b-4b40-8353-260ed54b47bc',
                    'house_kladr_id' => '5300000100001200042',
                    'house_type' => 'д',
                    'house_type_full' => 'дом',
                    'house' => '15',
                    'lat' => 58.5386129,
                    'lng' => 31.2448594,
                    'fias_id' => '048c928d-811b-4b40-8353-260ed54b47bc',
                    'kladr_id' => '5300000100001200042',
                    'qc_geo' => 0,
                ],
            ],
        ];
    }
}
