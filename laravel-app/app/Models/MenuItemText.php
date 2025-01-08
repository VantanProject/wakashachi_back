<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MenuItemText extends Model
{
    protected $fillable = [
        'menu_item_id',
        'color',
    ];

    public function menuItem(): HasOne
    {
        return $this->hasOne(MenuItem::class);
    }
    public function textTranslations(): HasMany
    {
        return $this->hasMany(TextTranslation::class);
    }
}
