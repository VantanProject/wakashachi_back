<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MenuItem extends Model
{
    protected $fillable = ["merch_id","manu_page_id","width","height","top","left"];

    public function menuPage(): BelongsTo
    {
        return $this->belongsTo(MenuPage::class);
    }
    public function merch(): BelongsTo
    {
        return $this->belongsTo(Merch::class);
    }
}
