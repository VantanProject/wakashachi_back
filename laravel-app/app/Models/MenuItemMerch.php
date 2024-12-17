<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class MenuItemMerch extends Model
{
    protected $fillable = [
        'menu_item_id',
        'merch_id',
    ];

    public function merch(): BelongsTo
    {
        return $this->belongsTo(Merch::class);
    }

    public function menuItem(): HasOne
    {
        return $this->hasOne(MenuItem::class);
    }
}
