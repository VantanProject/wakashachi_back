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
            $table->string('image');
            $table->timestamps();
        });

        DB::table('allergies')->insert([
            ['name' => '卵', 'image' => 'egg'],
            ['name' => '乳', 'image' => 'milk'],
            ['name' => '小麦', 'image' => 'wheat'],
            ['name' => 'そば', 'image' => 'soba'],
            ['name' => '落花生', 'image' => 'peanuts'],
            ['name' => 'えび', 'image' => 'shrimp'],
            ['name' => 'かに', 'image' => 'crab'],
            ['name' => 'あわび', 'image' => 'abalone'],
            ['name' => 'いか', 'image' => 'squid'],
            ['name' => 'いくら', 'image' => 'salmonRoe'],
            ['name' => 'オレンジ', 'image' => 'orange'],
            ['name' => 'キウイ', 'image' => 'kiwi'],
            ['name' => '牛肉', 'image' => 'beef'],
            ['name' => 'くるみ', 'image' => 'walnut'],
            ['name' => 'さけ', 'image' => 'sake'],
            ['name' => 'さば', 'image' => 'saba'],
            ['name' => '大豆', 'image' => 'soybean'],
            ['name' => '鶏肉', 'image' => 'chicken'],
            ['name' => 'バナナ', 'image' => 'banana'],
            ['name' => '豚肉', 'image' => 'pork'],
            ['name' => 'まつたけ', 'image' => 'matsutake'],
            ['name' => 'もも', 'image' => 'peach'],
            ['name' => 'やまいも', 'image' => 'yam'],
            ['name' => 'りんご', 'image' => 'apple'],
            ['name' => 'ゼラチン', 'image' => 'gelatin'],
            ['name' => 'ごま', 'image' => 'sesame'],
            ['name' => 'カシューナッツ', 'image' => 'cashew'],
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
