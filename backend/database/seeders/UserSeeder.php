<?php

namespace Database\Seeders;

use App\Enums\CourierProfileStatus;
use App\Enums\CourierVehicle;
use App\Models\CourierProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@mail.com'],
            [
                'phone'     => '79990000000',
                'name'      => 'Администратор',
                'is_admin'  => true,
                'is_banned' => false,
                'password'  => Hash::make('admin123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'owner@mail.com'],
            [
                'phone'     => '79990000001',
                'name'      => 'Владелец ресторана',
                'is_admin'  => false,
                'is_banned' => false,
                'password'  => Hash::make('owner123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'customer@mail.com'],
            [
                'phone'     => '79990000004',
                'name'      => 'Покупатель',
                'is_admin'  => false,
                'is_banned' => false,
                'password'  => Hash::make('customer123'),
            ]
        );

        $courier = User::updateOrCreate(
            ['email' => 'courier@mail.com'],
            [
                'phone'     => '79990000005',
                'name'      => 'Курьер',
                'is_admin'  => false,
                'is_banned' => false,
                'password'  => Hash::make('courier123'),
            ]
        );

        CourierProfile::updateOrCreate(
            ['user_id' => $courier->id],
            [
                'status' => CourierProfileStatus::ACTIVE->value,
                'vehicle' => CourierVehicle::CAR->value,
                'rating' => 5.0,
            ]
        );

        User::updateOrCreate(
            ['email' => 'manager@mail.com'],
            [
                'phone'     => '79990000002',
                'name'      => 'Менеджер Ресторана',
                'is_admin'  => false,
                'is_banned' => false,
                'password'  => Hash::make('manager123'),
            ]
        );

        User::updateOrCreate(
            ['email' => 'staff@mail.com'],
            [
                'phone'     => '79990000003',
                'name'      => 'Сотрудник Ресторана',
                'is_admin'  => false,
                'is_banned' => false,
                'password'  => Hash::make('staff123'),
            ]
        );
    }
}
