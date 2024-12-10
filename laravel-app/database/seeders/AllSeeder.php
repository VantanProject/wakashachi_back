<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
use App\Models\Menu;
use App\Models\MenuPage;
use App\Models\MenuItem;

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
            "email" => "wakashachi@example.com",
            "password" => Hash::make("wsy12345"),
            "company_id" => 1,
        ]);

        Menu::create([
            "company_id" => 1,
            "name" => "メニュー1",
        ]);

        MenuPage::create([
            "menu_id" => 1,
            "count" => 1,
        ]);

        MenuItem::create([
            "menu_id" => 1,
            "menu_page_id" => 1,
            "width" => 1,
            "height" => 1,
            "top" => 1,
            "left" => 1,
        ]);
    }
}