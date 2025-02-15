<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;
use App\Models\Company;
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
    }
}
