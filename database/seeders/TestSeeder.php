<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Company;
use App\Models\Equipment;
use App\Models\EquipmentRentalTerm;
use App\Models\Location;
use App\Models\News;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RentalRequest;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class TestSeeder extends Seeder
{
    public function run(): void
    {
        if (!Role::where('name', 'platform_super')->exists()) {
            Role::create(['name' => 'platform_super', 'guard_name' => 'web']);
        }
        if (!Role::where('name', 'company_admin')->exists()) {
            Role::create(['name' => 'company_admin', 'guard_name' => 'web']);
        }

        $admin = User::factory()->create([
            'name' => 'Admin Test',
            'email' => 'admin@test.com',
            'password' => Hash::make('password'),
        ]);
        $admin->assignRole('platform_super');

        $lessorCompany = Company::factory()->create([
            'legal_name' => 'ООО "Техника в аренду"',
            'inn' => '7701234567',
            'is_lessor' => true,
            'is_lessee' => false,
            'status' => 'verified',
        ]);

        $lessorUser = User::factory()->create([
            'name' => 'Арендодатель Тест',
            'email' => 'lessor@test.com',
            'password' => Hash::make('password'),
            'company_id' => $lessorCompany->id,
        ]);
        $lessorUser->assignRole('company_admin');

        $lesseeCompany = Company::factory()->create([
            'legal_name' => 'ООО "Стройка"',
            'inn' => '7707654321',
            'is_lessor' => false,
            'is_lessee' => true,
            'status' => 'verified',
        ]);

        $lesseeUser = User::factory()->create([
            'name' => 'Арендатор Тест',
            'email' => 'lessee@test.com',
            'password' => Hash::make('password'),
            'company_id' => $lesseeCompany->id,
        ]);
        $lesseeUser->assignRole('company_admin');

        $category = Category::firstOrCreate(['name' => 'Экскаваторы']);
        $category2 = Category::firstOrCreate(['name' => 'Бульдозеры']);

        $location1 = Location::factory()->create([
            'name' => 'Москва',
            'company_id' => $lessorCompany->id,
            'address' => 'г. Москва, ул. Строителей, 1',
        ]);
        $location2 = Location::factory()->create([
            'name' => 'СПб',
            'company_id' => $lessorCompany->id,
            'address' => 'г. Санкт-Петербург, ул. Инженерная, 5',
        ]);

        $eq1 = Equipment::factory()->create([
            'title' => 'Экскаватор JCB',
            'category_id' => $category->id,
            'company_id' => $lessorCompany->id,
            'location_id' => $location1->id,
            'is_approved' => true,
            'is_platform_owned' => false,
        ]);
        EquipmentRentalTerm::factory()->create([
            'equipment_id' => $eq1->id,
            'price_per_hour' => 1500,
        ]);

        $eq2 = Equipment::factory()->create([
            'title' => 'Бульдозер Caterpillar',
            'category_id' => $category2->id,
            'company_id' => $lessorCompany->id,
            'location_id' => $location2->id,
            'is_approved' => true,
            'is_platform_owned' => false,
        ]);
        EquipmentRentalTerm::factory()->create([
            'equipment_id' => $eq2->id,
            'price_per_hour' => 2000,
        ]);

        RentalRequest::factory()->create([
            'user_id' => $lesseeUser->id,
            'company_id' => $lesseeCompany->id,
            'status' => 'active',
        ]);

        News::factory()->create([
            'title' => 'Тестовая новость',
            'content' => 'Содержание тестовой новости',
            'category' => 'all',
            'is_active' => true,
            'created_by' => $admin->id,
        ]);

        $order = Order::factory()->create([
            'user_id' => $lesseeUser->id,
            'lessee_company_id' => $lesseeCompany->id,
            'lessor_company_id' => $lessorCompany->id,
            'status' => 'active',
            'total_amount' => 15000,
        ]);
        OrderItem::factory()->create([
            'order_id' => $order->id,
            'equipment_id' => $eq1->id,
            'total_price' => 15000,
        ]);
    }
}
