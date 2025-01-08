<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
use App\Models\Menu;
use App\Models\MenuPage;
use App\Models\MenuItem;
use App\Models\Merch;
use App\Models\MerchTranslation;
use App\Models\MenuItemMerch;
class AllSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Company::create([
            "name" => "若鯱家",
        ]);

        User::create([
            "name" => "若鯱家テストユーザー",
            "email" => "wakashachi@example.com",
            "password" => Hash::make("wsy12345"),
            "company_id" => 1,
        ]);

        Menu::create([
            "company_id" => 1,
            "name" => "メニュー1",
            "color" => "#FF0000",
        ]);

        MenuPage::insert([
            [
                "menu_id" => 1,
                "count" => 1,
            ],
            [
                "menu_id" => 1,
                "count" => 2,
            ],
            [
                "menu_id" => 1,
                "count" => 3,
            ],
            [
                "menu_id" => 1,
                "count" => 4,
            ]
        ]);

        Merch::insert([
            [
                "company_id" => 1,
                'img_url' => 'https://example.com/images/text1.jpg',
                'price' => 1000,
            ],
            [
                "company_id" => 1,
                'img_url' => 'https://example.com/images/text2.jpg',
                'price' => 2000,
            ],
        ]);

        MenuItem::insert([
            [
                "menu_page_id" => 1,
                "width" => 2,
                "height" => 2,
                "top" => 2,
                "left" => 2,
                "type" => "merch"
            ],
            [
                "menu_page_id" => 1,
                "width" => 4,
                "height" => 4,
                "top" => 4,
                "left" => 4,
                "type" => "merch"
            ],
            [
                "menu_page_id" => 2,
                "width" => 2,
                "height" =>2,
                "top" => 2,
                "left" => 2,
                "type" => "merch"
            ],
            [
                "menu_page_id" => 2,
                "width" => 4,
                "height" => 4,
                "top" => 4,
                "left" => 4,
                "type" => "merch"
            ]
        ]);
        MerchTranslation::insert([
            [
                "merch_id" => 1,
                "language_id" => 1,
                "name" => "テストA",
            ],
            [
                "merch_id" => 1,
                "language_id" => 2,
                "name" => "Test A",
            ],
            [
                "merch_id" => 1,
                "language_id" => 3,
                "name" => "测试A",
            ],
            [
                "merch_id" => 1,
                "language_id" => 4,
                "name" => "테스트A",
            ],
            [
                "merch_id" => 2,
                "language_id" => 1,
                "name" => "テストB",
            ],
            [
                "merch_id" => 2,
                "language_id" => 2,
                "name" => "Test B",
            ],
            [
                "merch_id" => 2,
                "language_id" => 3,
                "name" => "测试B",
            ],
            [
                "merch_id" => 2,
                "language_id" => 4,
                "name" => "테스트B",
            ]
        ]);
        MenuItemMerch::insert([
            [
                "menu_item_id" => 1,
                "merch_id" => 1,
            ],
            [
                "menu_item_id" => 2,
                "merch_id" => 1,
            ],
            [
                "menu_item_id" => 3,
                "merch_id" => 2,
            ],
            [
                "menu_item_id" => 4,
                "merch_id" => 2,
            ],
        ]);
    }
}
