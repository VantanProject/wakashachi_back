<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('allergies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        DB::table('allergies')->insert([
            ['name' => '卵'],
            ['name' => '乳'],
            ['name' => '小麦'],
            ['name' => 'そば'],
            ['name' => '落花生'],
            ['name' => 'えび'],
            ['name' => 'かに'],
            ['name' => 'あわび'],
            ['name' => 'いか'],
            ['name' => 'いくら'],
            ['name' => 'オレンジ'],
            ['name' => 'キウイ'],
            ['name' => '牛肉'],
            ['name' => 'くるみ'],
            ['name' => 'さけ'],
            ['name' => 'さば'],
            ['name' => '大豆'],
            ['name' => '鶏肉'],
            ['name' => 'バナナ'],
            ['name' => '豚肉'],
            ['name' => 'まつたけ'],
            ['name' => 'もも'],
            ['name' => 'やまいも'],
            ['name' => 'りんご'],
            ['name' => 'ゼラチン'],
            ['name' => 'ごま'],
            ['name' => 'カシューナッツ'],
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('allergies');
    }
};
