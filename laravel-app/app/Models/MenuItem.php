<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MenuItem extends Model
{
    protected $fillable = ["merch_id", "manu_page_id", "width", "height", "top", "left"];

    public function menuPage(): BelongsTo
    {
        return $this->belongsTo(MenuPage::class);
    }

    public function menuItemTexts(): HasOne
    {
        return $this->hasOne(MenuItemText::class);
    }

    public function menuItemMerch(): HasOne
    {
        return $this->hasOne(MenuItemMerch::class);
    }
}
